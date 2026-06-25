"use strict";
(self["webpackChunkdebug_log_viewer"] = self["webpackChunkdebug_log_viewer"] || []).push([["front_assets_js_components_cleanup-handler_js"],{

/***/ "./front/assets/js/components/cleanup-handler.js"
/*!*******************************************************!*\
  !*** ./front/assets/js/components/cleanup-handler.js ***!
  \*******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var bootstrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils */ "./front/assets/js/utils.js");
/* harmony import */ var _schedule_manager__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./schedule-manager */ "./front/assets/js/components/schedule-manager.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery, ajaxurl */



let cleanupScheduleManager = null;
function refreshCleanupStats() {
  const $ = jQuery;
  return $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
      action: 'dbg_lv_get_cleanup_stats',
      wp_nonce: window.dbg_lv_backend_data.ajax_nonce
    },
    success: function (response) {
      if (response.success && response.data) {
        const {
          stats,
          history
        } = response.data;

        // Update progress bar
        if (stats.percentage_used > 0) {
          const clampedPercentage = Math.min(stats.percentage_used, 100);
          const progressClass = clampedPercentage > 90 ? 'bg-danger' : clampedPercentage > 70 ? 'bg-warning' : 'bg-success';
          $('.progress-bar').css('width', clampedPercentage + '%').attr('aria-valuenow', clampedPercentage).removeClass('bg-danger bg-warning bg-success').addClass(progressClass).text(Math.round(clampedPercentage) + '%');

          // Update size display
          $('.progress').prev('.d-flex').find('span').last().text(parseFloat(stats.current_size_mb).toFixed(2) + ' / ' + parseFloat(stats.max_size_mb).toFixed(0) + ' MB');
        }

        // Update history table
        if (history && history.length > 0) {
          const tbody = $('#cleanup-history-table tbody');
          if (tbody.length) {
            tbody.empty();
            history.forEach(function (entry) {
              const statusBadge = entry.success ? '<span class="cleanup-badge cleanup-badge-success"><i class="fas fa-check-circle"></i> Success</span>' : '<span class="cleanup-badge cleanup-badge-failed"><i class="fas fa-times-circle"></i> Failed</span>';
              tbody.append('<tr>' + '<td><small>' + entry.date + '</small></td>' + '<td>' + statusBadge + '</td>' + '<td>' + entry.method + '</td>' + '<td>' + entry.size_before + '</td>' + '<td>' + entry.size_after + '</td>' + '<td>' + entry.reduction + '</td>' + '<td>' + entry.message + '</td>' + '</tr>');
            });
            initHistoryPagination();
          }
        }
      }
    }
  });
}
function initHistoryPagination() {
  const $ = jQuery;
  const $table = $('#cleanup-history-table');
  if (!$table.length) return;
  const $rows = $table.find('tbody tr');
  const totalRows = $rows.length;
  const perPage = 10;
  const totalPages = Math.ceil(totalRows / perPage);
  if (totalPages <= 1) {
    $('#cleanup-history-pagination').hide();
    $rows.show();
    return;
  }
  $('#cleanup-history-pagination').show();
  function showPage(page) {
    const start = (page - 1) * perPage;
    const end = start + perPage;
    $rows.hide();
    $rows.slice(start, end).show();
    const showing = Math.min(end, totalRows);
    $('#cleanup-pagination-info').text('Showing ' + (start + 1) + '–' + showing + ' of ' + totalRows + ' entries');
    const $controls = $('#cleanup-pagination-controls');
    $controls.empty();
    const $prev = $('<button>').addClass('cleanup-page-btn').prop('disabled', page === 1).html('<i class="fa-solid fa-chevron-left"></i>').on('click', function () {
      if (page > 1) showPage(page - 1);
    });
    $controls.append($prev);
    const range = [];
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || Math.abs(i - page) <= 1) range.push(i);
    }
    let prev = null;
    range.forEach(function (p) {
      if (prev && p - prev > 1) {
        $controls.append($('<span>').addClass('cleanup-page-ellipsis').text('…'));
      }
      const $btn = $('<button>').addClass('cleanup-page-btn' + (p === page ? ' active' : '')).text(p).on('click', function () {
        showPage(p);
      });
      $controls.append($btn);
      prev = p;
    });
    const $next = $('<button>').addClass('cleanup-page-btn').prop('disabled', page === totalPages).html('<i class="fa-solid fa-chevron-right"></i>').on('click', function () {
      if (page < totalPages) showPage(page + 1);
    });
    $controls.append($next);
  }
  showPage(1);
}
function initCleanupHandlers() {
  const $ = jQuery;
  const $form = $('#dbg_lv_cleanup_settings_form');
  const $button = $('#dbg_lv_save_cleanup_settings');

  // Check if user has premium access
  const isPremium = window.dbg_lv_backend_data && window.dbg_lv_backend_data.is_premium === '1';
  // Update form UI based on enabled state
  function updateFormUI() {
    const isEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true';

    // For premium users: Enable fields when disabled (so user can configure), lock fields when enabled
    if (isPremium) {
      $('#dbg_lv_max_log_size, #dlv_cleanup_method, #dlv_retention_percentage, #dlv_cleanup_schedule, #dlv_email_notifications').prop('disabled', isEnabled);
    }

    // Update button text and style
    if (isEnabled) {
      $button.val((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('disable')).removeClass('btn-primary').addClass('btn-outline-danger disable').prop('disabled', false);
    } else {
      $button.val((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('enable')).removeClass('btn-outline-danger disable').addClass('btn-primary').prop('disabled', false);
    }
  }

  // Show/hide retention percentage based on cleanup method
  function toggleRetentionField() {
    const method = $('#dlv_cleanup_method').val();
    const truncateMethod = $('#dlv_cleanup_method').data('truncate-method');
    if (method === truncateMethod) {
      $('#dlv_retention_row').show();
    } else {
      $('#dlv_retention_row').hide();
    }
  }

  // Initialize on page load
  updateFormUI();
  $('#dlv_cleanup_method').on('change', toggleRetentionField);
  toggleRetentionField();

  // Handle form submission
  $form.on('submit', function (e) {
    e.preventDefault();
    const isCurrentlyEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true';
    const originalValue = $button.val();
    $button.prop('disabled', true).val((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('loading_in_process'));

    // Toggle the enabled state
    const newEnabledState = !isCurrentlyEnabled;
    const data = {
      action: 'dbg_lv_save_cleanup_settings',
      cleanup_enabled: newEnabledState ? 1 : 0,
      max_log_size: $('#dbg_lv_max_log_size').val(),
      cleanup_method: $('#dlv_cleanup_method').val(),
      retention_percentage: $('#dlv_retention_percentage').val(),
      cleanup_schedule: $('#dlv_cleanup_schedule').val(),
      email_notifications: $('#dlv_email_notifications').is(':checked') ? 1 : 0,
      wp_nonce: $button.data('nonce')
    };
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data,
      success: function (response) {
        if (response.success) {
          (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)(response.data.message || (0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('settings_saved_successfully'), 'success');

          // Update form state dynamically without page reload
          $form.data('cleanup-enabled', newEnabledState);

          // Update UI to reflect new state
          updateFormUI();
          if (newEnabledState) {
            // Show schedule display with dynamic updates
            if (cleanupScheduleManager) {
              cleanupScheduleManager.show();
            }
          } else {
            // Hide schedule display
            if (cleanupScheduleManager) {
              cleanupScheduleManager.hide();
            }
          }
          $button.prop('disabled', false);
        } else {
          (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)(response.data.message || (0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('failed_to_save_settings'), 'error');
          $button.prop('disabled', false).val(originalValue);
        }
      },
      error: function () {
        (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('error_saving_settings'), 'error');
        $button.prop('disabled', false).val(originalValue);
      }
    });
  });

  // Handle button click as form submission
  $button.on('click', function (e) {
    e.preventDefault();
    $form.trigger('submit');
  });

  // Handle "Run Manually" button with modal confirmation
  $('#dbg_lv_run_cleanup_now').on('click', function () {
    const modalElement = $('#cleanup-confirmation-modal');
    const modal = new bootstrap__WEBPACK_IMPORTED_MODULE_0__.Modal(modalElement[0]);
    const $runButton = $(this);
    $('#cancel-cleanup').off('click').on('click', function () {
      modal.hide();
    });
    $('#confirm-cleanup').off('click').on('click', function () {
      const $confirmBtn = $(this);
      const originalText = $confirmBtn.text();

      // Show loading state
      $confirmBtn.prop('disabled', true).text((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('running'));
      $runButton.prop('disabled', true);
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'dbg_lv_run_manual_cleanup',
          wp_nonce: window.dbg_lv_backend_data.ajax_nonce
        },
        success: function (response) {
          modal.hide();
          if (response.success) {
            (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)(response.data.message || (0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('cleanup_completed'), 'success');

            // Instead of reloading, fetch fresh stats and update UI
            refreshCleanupStats();

            // Re-enable buttons
            $confirmBtn.prop('disabled', false).text(originalText);
            $runButton.prop('disabled', false);
          } else {
            (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)(response.data.message || (0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('cleanup_failed'), 'error');
            $confirmBtn.prop('disabled', false).text(originalText);
            $runButton.prop('disabled', false);
          }
        },
        error: function () {
          modal.hide();
          (0,_utils__WEBPACK_IMPORTED_MODULE_1__.showToast)((0,_utils__WEBPACK_IMPORTED_MODULE_1__.t)('error_during_cleanup'), 'error');
          $confirmBtn.prop('disabled', false).text(originalText);
          $runButton.prop('disabled', false);
        }
      });
    });
    modal.show();
  });

  // Initialize cleanup schedule display with dynamic updates
  cleanupScheduleManager = new _schedule_manager__WEBPACK_IMPORTED_MODULE_2__.ScheduleManager('next-cleanup-info', 'dbg_lv_get_cleanup_schedule');

  // Only show if cleanup is currently enabled
  const isEnabled = $form.data('cleanup-enabled') === true || $form.data('cleanup-enabled') === 'true';
  if (isEnabled) {
    cleanupScheduleManager.show();
  }

  // Refresh history button
  $('#cleanup-history-refresh').on('click', function () {
    const $btn = $(this);
    $btn.addClass('spinning');
    refreshCleanupStats().always(function () {
      $btn.removeClass('spinning');
    });
  });

  // Init client-side history pagination
  initHistoryPagination();
}
jQuery(document).ready(function () {
  initCleanupHandlers();
});

/***/ },

/***/ "./front/assets/js/components/schedule-manager.js"
/*!********************************************************!*\
  !*** ./front/assets/js/components/schedule-manager.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ScheduleManager: () => (/* binding */ ScheduleManager)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils.js */ "./front/assets/js/utils.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery, ajaxurl, dbg_lv_backend_data */


/**
 * Generic Schedule Manager
 * Handles dynamic display and countdown for scheduled tasks
 */
class ScheduleManager {
  constructor(containerId, ajaxAction) {
    this.container = jQuery(`#${containerId}`);
    this.timeElement = this.container.find('.next-time');
    this.countdownElement = this.container.find('.next-countdown');
    this.ajaxAction = ajaxAction;
    this.updateInterval = null;
    this.targetTimestamp = null;
  }
  async updateScheduleDisplay() {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: this.ajaxAction,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data || (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('request_error'));
      }
      if (response.scheduled) {
        this.targetTimestamp = response.timestamp;
        this.timeElement.text(response.formatted_time);
        this.countdownElement.text((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('in') + ' ' + response.time_until);
        this.container.show();

        // Start countdown timer
        this.startCountdownTimer();
      } else {
        this.hide();
      }
    } catch (error) {
      console.error('Failed to update schedule display:', error);
      this.hide();
    }
  }
  startCountdownTimer() {
    this.stopCountdownTimer();
    this.updateInterval = setInterval(() => {
      if (!this.targetTimestamp) {
        return;
      }
      const now = Math.floor(Date.now() / 1000);
      const timeDiff = this.targetTimestamp - now;
      if (timeDiff <= 0) {
        this.stopCountdownTimer();
        // Refresh to get new schedule
        this.updateScheduleDisplay();
        return;
      }
      const timeUntil = this.formatTimeRemaining(timeDiff);
      this.countdownElement.text((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('in') + ' ' + timeUntil);
    }, 60000); // Update every minute
  }
  stopCountdownTimer() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
      this.updateInterval = null;
    }
  }
  formatTimeRemaining(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor(seconds % 86400 / 3600);
    const minutes = Math.floor(seconds % 3600 / 60);
    if (days > 0) {
      if (hours > 0) {
        return `${days} ${days === 1 ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('day') : (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('days')} ${hours} ${hours === 1 ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('hour') : (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('hours')}`;
      } else {
        return `${days} ${days === 1 ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('day') : (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('days')}`;
      }
    } else if (hours > 0) {
      return `${hours} ${hours === 1 ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('hour') : (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('hours')}`;
    } else {
      return `${minutes} ${minutes === 1 ? (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('minute') : (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('minutes')}`;
    }
  }
  hide() {
    this.container.hide();
    this.stopCountdownTimer();
  }
  show() {
    this.updateScheduleDisplay();
  }
  destroy() {
    this.stopCountdownTimer();
  }
}

/***/ },

/***/ "./front/assets/js/utils.js"
/*!**********************************!*\
  !*** ./front/assets/js/utils.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initScrollToTopButton: () => (/* binding */ initScrollToTopButton),
/* harmony export */   showToast: () => (/* binding */ showToast),
/* harmony export */   t: () => (/* binding */ t),
/* harmony export */   updateFilterCount: () => (/* binding */ updateFilterCount)
/* harmony export */ });
/* harmony import */ var bootstrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global dbg_lv_backend_data, $ */


/**
 * Translation function
 * @param {string} key - The key to translate
 * @returns {string} - The translated string
 */
function t(key) {
  const {
    phrases
  } = dbg_lv_backend_data;
  if (phrases && phrases[key]) {
    return phrases[key];
  }
  console.warn(`Phrase ${key} not found`);
  return key;
}
function showToast(message, level) {
  const $toast = $('#custom-toast');
  const $toastBody = $('#toast-body');
  if (!$toast.length) {
    return; // Prevent errors if the toast element is missing
  }

  // Handle Error objects and jQuery jqXHR objects (e.g. wp_send_json_error with 4xx status)
  let displayMessage;
  if (message instanceof Error) {
    displayMessage = message.message;
  } else if (message && typeof message === 'object' && message.responseJSON) {
    displayMessage = message.responseJSON.data || message.responseJSON.message || message.statusText || String(message);
  } else {
    displayMessage = message;
  }

  // Set the message
  $toastBody.text(displayMessage);

  // Determine the level (success, error, warning)
  let toastClass = 'bg-success';
  if (level === 'error') {
    toastClass = 'bg-danger';
  } else if (level === 'warning') {
    toastClass = 'bg-warning';
  }

  // Remove previous level classes and add the new one to the toast container
  $toast.removeClass('bg-success bg-danger bg-warning').addClass(toastClass);

  // Initialize and show the toast
  const toastInstance = new bootstrap__WEBPACK_IMPORTED_MODULE_0__.Toast($toast[0]);
  toastInstance.show();
}

/**
 * Initialize the scroll to top button
 */
function initScrollToTopButton() {
  $('body').append(`<div class="scroll-top" id="dbg_lv_scrollToTopButton" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 384 512">
            <path fill="#4f6df5" d="M214.6 41.4c-12.5-12.5-32.8-12.5-45.3 0l-160 160c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L160 141.2V448c0 17.7 14.3 32 32 32s32-14.3 32-32V141.2L329.4 246.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3l-160-160z"/>
        </svg>
    </div>`);
  $(document).on('scroll', function () {
    if ($(this).scrollTop() > 100) {
      $('#dbg_lv_scrollToTopButton').fadeIn();
    } else {
      $('#dbg_lv_scrollToTopButton').fadeOut();
    }
  });
  $('#dbg_lv_scrollToTopButton').on('click', function () {
    $('html, body').animate({
      scrollTop: 0
    }, 500, 'linear');
    return false;
  });
}

/**
 * Updates the filter count badge on the filter button
 * @param {object} table - The DataTable instance
 */
function updateFilterCount(table) {
  let conditionsCount = 0;
  const filterButton = $('.search-builder-btn');
  const filterCount = filterButton.find('.filter-counter');
  const searchBuilderCriteria = table.searchBuilder.getDetails();
  const isTableFiltered = table.search() !== '' || table.rows({
    search: 'applied'
  }).count() !== table.rows().count();
  if (searchBuilderCriteria && searchBuilderCriteria.criteria && isTableFiltered) {
    conditionsCount = searchBuilderCriteria.criteria.filter(criteria => {
      // Check if value exists and is not empty
      if (!criteria.value) return false;

      // Check if this is just an empty condition row
      if (criteria.condition === undefined || criteria.condition === '') return false;
      if (Array.isArray(criteria.value)) {
        return criteria.value.length > 0 && criteria.value.some(v => v && String(v).trim() !== '');
      }
      return String(criteria.value).trim() !== '';
    }).length;
  }
  if (conditionsCount > 0) {
    filterCount.text(` (${conditionsCount})`).show();
  } else {
    filterCount.hide();
  }
}

/***/ }

}]);