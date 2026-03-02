/* global jQuery, ajaxurl */
import { Modal } from 'bootstrap'
import { showToast, t } from '../utils'
import { ScheduleManager } from './schedule-manager'

let cleanupScheduleManager = null

function refreshCleanupStats() {
  const $ = jQuery

  // Fetch fresh stats and history
  $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
      action: 'dbg_lv_get_cleanup_stats',
      wp_nonce: window.dbg_lv_backend_data.ajax_nonce
    },
    success: function (response) {
      if (response.success && response.data) {

        const { stats, history } = response.data;

        // Update progress bar
        if (stats.percentage_used > 0) {
          const clampedPercentage = Math.min(stats.percentage_used, 100)
          const progressClass = clampedPercentage > 90 ? 'bg-danger' : (clampedPercentage > 70 ? 'bg-warning' : 'bg-success')
          $('.progress-bar')
            .css('width', clampedPercentage + '%')
            .attr('aria-valuenow', clampedPercentage)
            .removeClass('bg-danger bg-warning bg-success')
            .addClass(progressClass)
            .text(Math.round(clampedPercentage) + '%')

          // Update size display
          $('.progress').prev('.d-flex').find('span').last()
            .text(Number(stats.current_size_mb).toFixed(2) + ' / ' + Number(stats.max_size_mb).toFixed(0) + ' MB')
        }

        // Update history table
        if (history && history.length > 0) {
          const tbody = $('#cleanup-history-table tbody')
          if (tbody.length) {
            tbody.empty()

            history.forEach(function (entry) {
              const statusBadge = entry.success
                ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Success</span>'
                : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Failed</span>'

              tbody.append(
                '<tr>' +
                '<td><small>' + entry.date + '</small></td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' + entry.method + '</td>' +
                '<td>' + entry.size_before + '</td>' +
                '<td>' + entry.size_after + '</td>' +
                '<td>' + entry.reduction + '</td>' +
                '<td>' + entry.message + '</td>' +
                '</tr>'
              )
            })
          }
        }
      }
    }
  })
}

function initCleanupHandlers() {
  const $ = jQuery
  const $form = $('#dbg_lv_cleanup_settings_form')
  const $button = $('#dbg_lv_save_cleanup_settings')

  // Check if user has premium access
  const isPremium = window.dbg_lv_backend_data && window.dbg_lv_backend_data.is_premium === '1';
  // Update form UI based on enabled state
  function updateFormUI() {
    const isEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true'

    // For premium users: Enable fields when disabled (so user can configure), lock fields when enabled
    if (isPremium) {
      $('#dbg_lv_max_log_size, #dlv_cleanup_method, #dlv_retention_percentage, #dlv_cleanup_schedule, #dlv_email_notifications').prop('disabled', isEnabled)
    }

    // Update button text and style
    if (isEnabled) {
      $button.val(t('disable')).removeClass('btn-primary').addClass('btn-danger').prop('disabled', false)
    } else {
      $button.val(t('enable')).removeClass('btn-danger').addClass('btn-primary').prop('disabled', false)
    }
  }

  // Show/hide retention percentage based on cleanup method
  function toggleRetentionField() {
    const method = $('#dlv_cleanup_method').val()
    const truncateMethod = $('#dlv_cleanup_method').data('truncate-method')

    if (method === truncateMethod) {
      $('#dlv_retention_row').show()
    } else {
      $('#dlv_retention_row').hide()
    }
  }

  // Initialize on page load
  updateFormUI()
  $('#dlv_cleanup_method').on('change', toggleRetentionField)
  toggleRetentionField()

  // Handle form submission
  $form.on('submit', function (e) {
    e.preventDefault()

    const isCurrentlyEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true'

    const originalValue = $button.val()
    $button.prop('disabled', true).val(t('loading_in_process'))

    // Toggle the enabled state
    const newEnabledState = !isCurrentlyEnabled

    const data = {
      action: 'dbg_lv_save_cleanup_settings',
      cleanup_enabled: newEnabledState ? 1 : 0,
      max_log_size: $('#dbg_lv_max_log_size').val(),
      cleanup_method: $('#dlv_cleanup_method').val(),
      retention_percentage: $('#dlv_retention_percentage').val(),
      cleanup_schedule: $('#dlv_cleanup_schedule').val(),
      email_notifications: $('#dlv_email_notifications').is(':checked') ? 1 : 0,
      wp_nonce: $button.data('nonce')
    }

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data,
      success: function (response) {
        if (response.success) {
          showToast(response.data.message || t('settings_saved_successfully'), 'success')

          // Update form state dynamically without page reload
          $form.data('cleanup-enabled', newEnabledState)

          // Update UI to reflect new state
          updateFormUI()

          if (newEnabledState) {
            // Show schedule display with dynamic updates
            if (cleanupScheduleManager) {
              cleanupScheduleManager.show()
            }
          } else {
            // Hide schedule display
            if (cleanupScheduleManager) {
              cleanupScheduleManager.hide()
            }
          }

          $button.prop('disabled', false)
        } else {
          showToast(response.data.message || t('failed_to_save_settings'), 'error')
          $button.prop('disabled', false).val(originalValue)
        }
      },
      error: function () {
        showToast(t('error_saving_settings'), 'error')
        $button.prop('disabled', false).val(originalValue)
      }
    })
  })

  // Handle button click as form submission
  $button.on('click', function (e) {
    e.preventDefault()
    $form.trigger('submit')
  })

  // Handle "Run Manually" button with modal confirmation
  $('#dbg_lv_run_cleanup_now').on('click', function () {
    const modalElement = $('#cleanup-confirmation-modal')
    const modal = new Modal(modalElement[0])
    const $runButton = $(this)

    $('#cancel-cleanup').off('click').on('click', function () {
      modal.hide()
    })

    $('#confirm-cleanup').off('click').on('click', function () {
      const $confirmBtn = $(this)
      const originalText = $confirmBtn.text()

      // Show loading state
      $confirmBtn.prop('disabled', true).text(t('running'))
      $runButton.prop('disabled', true)

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'dbg_lv_run_manual_cleanup',
          wp_nonce: window.dbg_lv_backend_data.ajax_nonce
        },
        success: function (response) {
          modal.hide()

          if (response.success) {
            showToast(response.data.message || t('cleanup_completed'), 'success')

            // Instead of reloading, fetch fresh stats and update UI
            refreshCleanupStats()

            // Re-enable buttons
            $confirmBtn.prop('disabled', false).text(originalText)
            $runButton.prop('disabled', false)
          } else {
            showToast(response.data.message || t('cleanup_failed'), 'error')
            $confirmBtn.prop('disabled', false).text(originalText)
            $runButton.prop('disabled', false)
          }
        },
        error: function () {
          modal.hide()
          showToast(t('error_during_cleanup'), 'error')
          $confirmBtn.prop('disabled', false).text(originalText)
          $runButton.prop('disabled', false)
        }
      })
    })

    modal.show()
  })

  // Initialize cleanup schedule display with dynamic updates
  cleanupScheduleManager = new ScheduleManager('next-cleanup-info', 'dbg_lv_get_cleanup_schedule')

  // Only show if cleanup is currently enabled
  const isEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true'
  if (isEnabled) {
    cleanupScheduleManager.show()
  }

  // Rotate chevron icon when details is opened/closed
  $('.cleanup-history-details').on('toggle', function() {
    const icon = $(this).find('summary i')
    if (this.open) {
      icon.removeClass('fa-chevron-down').addClass('fa-chevron-up')
    } else {
      icon.removeClass('fa-chevron-up').addClass('fa-chevron-down')
    }
  })
}

jQuery(document).ready(function() {
  initCleanupHandlers()
})
