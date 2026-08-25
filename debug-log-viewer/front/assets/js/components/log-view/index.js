/* global jQuery, dbg_lv_backend_data, posthog */
import { initScrollToTopButton, showToast, t } from '../../utils.js'
import { createDataTableConfig } from './table-config.js'
import { LogViewerApiService } from './api-service.js'
import { LogViewerUI } from './ui-utils.js'
import { LogViewerEventHandlers } from './event-handlers.js'
import { initEmailNotificationsForm } from './notification-utils.js'
import { initHelpScoutBeacon } from '../help-scout.js'
import { AlertHandler } from './alert-handler.js'
import { initLogPanels, appendPublicAccessWarning } from '../log-popover-renderer.js'

(async ($) => {
  initHelpScoutBeacon('055b8c26-4e0e-4352-a0f2-7e6f3e7c3336')

  // !(function (t, e) { let o, n, p, r; e.__SV || (window.posthog = e, e._i = [], e.init = function (i, s, a) { function g (t, e) { const o = e.split('.'); o.length == 2 && (t = t[o[0]], e = o[1]), t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } } (p = t.createElement('script')).type = 'text/javascript', p.crossOrigin = 'anonymous', p.async = !0, p.src = s.api_host.replace('.i.posthog.com', '-assets.i.posthog.com') + '/static/array.js', (r = t.getElementsByTagName('script')[0]).parentNode.insertBefore(p, r); let u = e; for (void 0 !== a ? u = e[a] = [] : a = 'posthog', u.people = u.people || [], u.toString = function (t) { let e = 'posthog'; return a !== 'posthog' && (e += '.' + a), t || (e += ' (stub)'), e }, u.people.toString = function () { return u.toString(1) + '.people (stub)' }, o = 'init Ie Ts Ms Ee Es Rs capture Ge calculateEventProperties Os register register_once register_for_session unregister unregister_for_session js getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey canRenderSurveyAsync identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty Ds Fs createPersonProfile Ls Ps opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing Cs debug I As getPageViewId captureTraceFeedback captureTraceMetric'.split(' '), n = 0; n < o.length; n++)g(u, o[n]); e._i.push([i, s, a]) }, e.__SV = 1) }(document, window.posthog || []))
  // posthog.init('phc_pYVBt9N6AieVgFXoQHj27wq3PwHLeujCZrNlXKSbYLv', {
  //   api_host: 'https://eu.i.posthog.com',
  //   defaults: '2025-05-24',
  //   person_profiles: 'always'
  // })

  const dataTableConfig = createDataTableConfig()
  const table = $('#dbg_lv_log-table').DataTable(dataTableConfig)

  // Sync Count column visibility with the saved group_entries preference
  const isGroupingOnInit = dbg_lv_backend_data.group_entries !== '0'
  table.column(3).visible(isGroupingOnInit)

  // Search functionality with mode support
  let searchMode = {
    smart: true, // Default: smart search enabled
    regex: false
  }

  function performSearch() {
    const searchValue = $('#dbg_lv_search_input').val().trim()

    if (!searchValue) {
      table.search('', false, false).draw()
      return
    }

    // Validate regex if regex mode is enabled
    if (searchMode.regex) {
      try {
        new RegExp(searchValue)
      } catch (e) {
        showToast(t('invalid_regex') || 'Invalid regular expression', 'error')
        return
      }
    }

    // Apply search with appropriate mode
    // DataTables.search(searchString, regex, smart, caseInsensitive)
    table.search(searchValue, searchMode.regex, searchMode.smart, true).draw()
  }

  // Search input handler
  $('#dbg_lv_search_input').on('input', performSearch)

  // Toggle smart search
  $('#dbg_lv_search_smart').on('click', function (e) {
    e.preventDefault()
    searchMode.smart = !searchMode.smart
    $(this).toggleClass('active', searchMode.smart)
    performSearch()
  })

  // Toggle regex search (Pro only)
  $('#dbg_lv_search_regex').on('click', function (e) {
    e.preventDefault()
    
    // Double-check it's not the disabled Pro badge button
    if ($(this).hasClass('pro-disabled')) {
      return
    }
    
    searchMode.regex = !searchMode.regex
    $(this).toggleClass('active', searchMode.regex)
    
    // When regex is enabled, disable smart search (they're mutually exclusive in DataTables)
    if (searchMode.regex) {
      searchMode.smart = false
      $('#dbg_lv_search_smart').removeClass('active').prop('disabled', true)
    } else {
      $('#dbg_lv_search_smart').prop('disabled', false)
    }
    
    performSearch()
  })

  // Initialize: Set smart search as active by default
  $('#dbg_lv_search_smart').addClass('active')

  const apiService = new LogViewerApiService(table)
  const uiUtils = new LogViewerUI(table)
  const eventHandlers = new LogViewerEventHandlers(table, apiService, uiUtils)
  const alertHandler = new AlertHandler()

  initScrollToTopButton()
  alertHandler.init()
  await apiService.fillInitialData()

  const $logPathIcons = $('.log-path-icons')
  if ($logPathIcons.length) {
    initLogPanels($logPathIcons)
  }

  $('#dbg_lv_log-table_processing').remove()

  // Only check if debug log is publicly accessible if the alert hasn't been dismissed
  if (!alertHandler.isCurrentlyDismissed()) {
    const result = await apiService.isDebugLogPubliclyAccessible()

    if (result.success && result.data.is_public) {
      if (result.data.info) {
        if ($logPathIcons.length){
          appendPublicAccessWarning($logPathIcons, result.data.info)
        }
      }
    }
  }

  uiUtils.setupTimeFilter()

  eventHandlers.init()

  uiUtils.startRelativeTimeUpdates()

  await initEmailNotificationsForm($('#dbg_lv_log_viewer_alerts_form'))

  if (dbg_lv_backend_data.log_updates_mode === 'AUTO') {
    uiUtils.startLiveUpdateLogs(() => apiService.updateLogs(() => uiUtils.animateRefreshButton()))
  }
})(jQuery)
