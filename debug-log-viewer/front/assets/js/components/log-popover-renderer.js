/* global $ */
import { Collapse } from 'bootstrap'
import { t } from '../utils.js'

export function initLogPanels ($container) {
  $('.log-path-collapse-panels .collapse').on('show.bs.collapse', function () {
    $container.find(`[data-bs-target="#${this.id}"]`).addClass('active')
  }).on('hide.bs.collapse', function () {
    $container.find(`[data-bs-target="#${this.id}"]`).removeClass('active')
  })
}

export function appendPublicAccessWarning ($container, warningData) {
  const $panel = $('#dbg_lv_warnings_panel')
  if (!$panel.length) return

  const $content = $panel.find('.log-panel-content')

  if (!$content.find('.log-panel-header').length) {
    $content.prepend(`<p class="log-panel-header">${t('log_file_status_warnings')}</p>`)
  }

  const statusClass = warningData.type === 'danger' ? 'text-danger' : 'text-warning'
  const keyAttr = warningData.key ? ` data-warning-key="${warningData.key}"` : ''
  $content.append(`
    <div class="d-flex align-items-start mb-2"${keyAttr}>
      <i class="fa-solid ${warningData.icon} ${statusClass} me-2 mt-1"></i>
      <div class="flex-grow-1">
        <strong>${warningData.title}</strong>
        <p>${warningData.message}</p>
        <a class="text-muted" id="dbg_lv_dismiss_alert">${t('remind_in_a_week')}</a>
      </div>
    </div>
  `)

  const $icon = $container.find('.log-warning-trigger')
  $icon.removeClass('d-none')
  if (warningData.type === 'danger') {
    $icon.removeClass('severity-warning').addClass('severity-danger')
  }
}

export function removePublicAccessWarning () {
  $('#dbg_lv_warnings_panel [data-warning-key="dbg_lv_public_log_alert_dismissed"]').remove()

  const $content = $('#dbg_lv_warnings_panel .log-panel-content')
  if (!$content.find('.d-flex.align-items-start').length) {
    $('.log-warning-trigger').addClass('d-none')
    const panel = document.getElementById('dbg_lv_warnings_panel')
    if (panel) Collapse.getOrCreateInstance(panel, { toggle: false }).hide()
  }
}
