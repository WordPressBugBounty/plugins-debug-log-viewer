"use strict";
(self["webpackChunkdebug_log_viewer"] = self["webpackChunkdebug_log_viewer"] || []).push([["front_assets_js_components_log-view_index_js"],{

/***/ "./front/assets/js/components/help-scout.js"
/*!**************************************************!*\
  !*** ./front/assets/js/components/help-scout.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initHelpScoutBeacon: () => (/* binding */ initHelpScoutBeacon)
/* harmony export */ });
/* global window, document */

/**
 * Initialize Help Scout Beacon chat
 * @param {string} beaconId - The Help Scout Beacon ID
 * @param {Object} userData - Optional user data for identification
 */
function initHelpScoutBeacon(beaconId) {
  let userData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  // Initialize the Beacon script
  ;
  (function (w, d, n) {
    function a() {
      const s = d.getElementsByTagName('script')[0];
      const b = d.createElement('script');
      b.type = 'text/javascript';
      b.async = true;
      b.src = 'https://beacon-v2.helpscout.net';
      s.parentNode.insertBefore(b, s);
    }
    if (w.Beacon = n = function (t, n, a) {
      w.Beacon.readyQueue.push({
        method: t,
        options: n,
        data: a
      });
    }, n.readyQueue = [], d.readyState === 'complete') {
      return a();
    }
    w.attachEvent ? w.attachEvent('onload', a) : w.addEventListener('load', a, false);
  })(window, document, window.Beacon || function () {});

  // Initialize with your Beacon ID
  window.Beacon('init', beaconId);
  window.Beacon('config', {
    display: {
      verticalOffset: 30,
      horizontalOffset: 25
    }
  });
  // If user data is provided, identify the user
  if (userData) {
    window.Beacon('identify', userData);
  }
}

/***/ },

/***/ "./front/assets/js/components/log-popover-renderer.js"
/*!************************************************************!*\
  !*** ./front/assets/js/components/log-popover-renderer.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   appendPublicAccessWarning: () => (/* binding */ appendPublicAccessWarning),
/* harmony export */   initLogPanels: () => (/* binding */ initLogPanels),
/* harmony export */   removePublicAccessWarning: () => (/* binding */ removePublicAccessWarning)
/* harmony export */ });
/* harmony import */ var bootstrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils.js */ "./front/assets/js/utils.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global $ */


function initLogPanels($container) {
  $('.log-path-collapse-panels .collapse').on('show.bs.collapse', function () {
    $container.find(`[data-bs-target="#${this.id}"]`).addClass('active');
  }).on('hide.bs.collapse', function () {
    $container.find(`[data-bs-target="#${this.id}"]`).removeClass('active');
  });
}
function appendPublicAccessWarning($container, warningData) {
  const $panel = $('#dbg_lv_warnings_panel');
  if (!$panel.length) return;
  const $content = $panel.find('.log-panel-content');
  if (!$content.find('.log-panel-header').length) {
    $content.prepend(`<p class="log-panel-header">${(0,_utils_js__WEBPACK_IMPORTED_MODULE_1__.t)('log_file_status_warnings')}</p>`);
  }
  const statusClass = warningData.type === 'danger' ? 'text-danger' : 'text-warning';
  const keyAttr = warningData.key ? ` data-warning-key="${warningData.key}"` : '';
  $content.append(`
    <div class="d-flex align-items-start mb-2"${keyAttr}>
      <i class="fa-solid ${warningData.icon} ${statusClass} me-2 mt-1"></i>
      <div class="flex-grow-1">
        <strong>${warningData.title}</strong>
        <p>${warningData.message}</p>
        <a class="text-muted" id="dbg_lv_dismiss_alert">${(0,_utils_js__WEBPACK_IMPORTED_MODULE_1__.t)('remind_in_a_week')}</a>
      </div>
    </div>
  `);
  const $icon = $container.find('.log-warning-trigger');
  $icon.removeClass('d-none');
  if (warningData.type === 'danger') {
    $icon.removeClass('severity-warning').addClass('severity-danger');
  }
}
function removePublicAccessWarning() {
  $('#dbg_lv_warnings_panel [data-warning-key="dbg_lv_public_log_alert_dismissed"]').remove();
  const $content = $('#dbg_lv_warnings_panel .log-panel-content');
  if (!$content.find('.d-flex.align-items-start').length) {
    $('.log-warning-trigger').addClass('d-none');
    const panel = document.getElementById('dbg_lv_warnings_panel');
    if (panel) bootstrap__WEBPACK_IMPORTED_MODULE_0__.Collapse.getOrCreateInstance(panel, {
      toggle: false
    }).hide();
  }
}

/***/ },

/***/ "./front/assets/js/components/log-view/alert-handler.js"
/*!**************************************************************!*\
  !*** ./front/assets/js/components/log-view/alert-handler.js ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AlertHandler: () => (/* binding */ AlertHandler)
/* harmony export */ });
/* harmony import */ var _log_popover_renderer_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../log-popover-renderer.js */ "./front/assets/js/components/log-popover-renderer.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global localStorage */


/**
 * Handles the alert dismissal functionality
 */
class AlertHandler {
  constructor() {
    this.dismissKey = 'dbg_lv_public_log_alert_dismissed';
    this.dismissSelector = '#dbg_lv_dismiss_alert';
    this.weekInMilliseconds = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds
  }

  /**
   * Initialize the alert handler
   */
  init() {
    // Add event delegation for dismiss buttons in dynamically created popovers
    $(document).off('click', this.dismissSelector).on('click', this.dismissSelector, function (e) {
      e.preventDefault();
      this.dismissForWeek();
      (0,_log_popover_renderer_js__WEBPACK_IMPORTED_MODULE_0__.removePublicAccessWarning)();
    }.bind(this));
  }

  /**
   * Dismiss the alert for a week
   */
  dismissForWeek() {
    // Store current timestamp in localStorage
    localStorage.setItem(this.dismissKey, new Date().getTime().toString());
  }

  /**
   * Check if the alert is currently dismissed
   * @return {boolean} True if the alert is currently dismissed
   */
  isCurrentlyDismissed() {
    const dismissedTimestamp = localStorage.getItem(this.dismissKey);
    if (dismissedTimestamp) {
      const currentTime = new Date().getTime();
      const dismissedTime = parseInt(dismissedTimestamp, 10);

      // If less than a week has passed, the alert is considered dismissed
      return currentTime - dismissedTime < this.weekInMilliseconds;
    }
    return false;
  }
}

/***/ },

/***/ "./front/assets/js/components/log-view/constants.js"
/*!**********************************************************!*\
  !*** ./front/assets/js/components/log-view/constants.js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DATETIME_FORMAT_ABSOLUTE: () => (/* binding */ DATETIME_FORMAT_ABSOLUTE),
/* harmony export */   DATETIME_FORMAT_RELATIVE: () => (/* binding */ DATETIME_FORMAT_RELATIVE)
/* harmony export */ });
// Datetime format constants
const DATETIME_FORMAT_ABSOLUTE = 'ABSOLUTE';
const DATETIME_FORMAT_RELATIVE = 'RELATIVE';

/***/ },

/***/ "./front/assets/js/components/log-view/email-recipients-manager.js"
/*!*************************************************************************!*\
  !*** ./front/assets/js/components/log-view/email-recipients-manager.js ***!
  \*************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   EmailRecipientsManager: () => (/* binding */ EmailRecipientsManager)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery */


/**
 * Handles multiple email inputs for alert notifications
 */
class EmailRecipientsManager {
  constructor(containerSelector = '#email-recipients-container') {
    this.container = jQuery(containerSelector);
    this.addButton = jQuery('#alerts-add-email-btn');
    this.maxEmails = 3;
    this.init();
  }

  /**
   * Initialize the email recipients manager
   */
  init() {
    if (!this.container.length) {
      return;
    }
    this.bindEvents();
    this.updateButtonStates();
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    // Add email button click (external button outside the container)
    this.addButton.on('click', e => {
      e.preventDefault();
      this.addEmailInput();
    });

    // Remove email button click
    this.container.on('click', '.remove-email-btn', e => {
      e.preventDefault();
      this.removeEmailInput(jQuery(e.currentTarget).closest('.email-input-row'));
    });

    // Update button states when inputs change
    this.container.on('input', 'input[type="email"]', () => {
      this.updateButtonStates();
    });
  }

  /**
   * Add a new email input row
   */
  addEmailInput() {
    const currentRows = this.container.find('.email-input-row');
    if (currentRows.length >= this.maxEmails) {
      return;
    }
    const newIndex = currentRows.length;
    const newRow = this.createEmailRow(newIndex, '');
    this.container.append(newRow);
    this.updateButtonStates();

    // Focus the new input
    newRow.find('input[type="email"]').focus();
  }

  /**
   * Remove an email input row
   */
  removeEmailInput(row) {
    const allRows = this.container.find('.email-input-row');

    // Don't remove if it's the only row
    if (allRows.length <= 1) {
      return;
    }
    row.remove();
    this.updateIndices();
    this.updateButtonStates();
  }

  /**
   * Create a new email input row
   */
  createEmailRow(index, email = '') {
    const row = jQuery(`
      <div class="email-input-row" data-index="${index}">
        <input type="email" name="emails[]" value="${email}" />
        <button type="button" class="alerts-remove-email-btn remove-email-btn" title="${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('remove_this_email')}">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    `);
    return row;
  }

  /**
   * Update button states based on current rows
   */
  updateButtonStates() {
    const rows = this.container.find('.email-input-row');
    const totalRows = rows.length;

    // Update external add button
    this.addButton.prop('disabled', totalRows >= this.maxEmails);

    // Update remove buttons
    rows.each((index, row) => {
      const $row = jQuery(row);
      const removeBtn = $row.find('.remove-email-btn');
      if (index === 0) {
        // First row: hide remove button if it's the only row
        removeBtn.toggle(totalRows > 1);
      } else {
        // Other rows: always show remove button
        removeBtn.show();
      }
    });
  }

  /**
   * Update data-index attributes after row removal
   */
  updateIndices() {
    this.container.find('.email-input-row').each((index, row) => {
      jQuery(row).attr('data-index', index);
    });
  }

  /**
   * Get all email values
   */
  getEmails() {
    return this.container.find('input[type="email"]').map((index, input) => {
      return jQuery(input).val().trim();
    }).get().filter(email => email !== '');
  }

  /**
   * Set email values
   */
  setEmails(emails) {
    // Clear existing rows
    this.container.empty();

    // Ensure we have at least one email input
    if (emails.length === 0) {
      emails = [''];
    }

    // Create rows for each email
    emails.forEach((email, index) => {
      const row = this.createEmailRow(index, email);
      this.container.append(row);
    });
    this.updateButtonStates();
  }

  /**
   * Validate all email inputs
   */
  validate() {
    const emails = this.getEmails();
    const errors = [];
    if (emails.length === 0) {
      errors.push((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('at_least_one_email_required'));
    }
    emails.forEach((email, index) => {
      if (!this.isValidEmail(email)) {
        errors.push((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('email_not_valid').replace('%d', index + 1));
      }
    });
    return {
      isValid: errors.length === 0,
      errors
    };
  }

  /**
   * Simple email validation - ReDoS safe
   */
  isValidEmail(email) {
    // Prevent ReDoS attacks by using length check and safe regex
    if (!email || email.length > 254) {
      return false;
    }

    // Safe regex pattern without potential for catastrophic backtracking
    const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    return emailRegex.test(email);
  }

  /**
   * Enable/disable all email inputs
   */
  setEnabled(enabled) {
    this.container.find('input[type="email"]').prop('disabled', !enabled);
    this.container.find('.remove-email-btn').prop('disabled', !enabled);
    this.addButton.prop('disabled', !enabled);
  }
}

/***/ },

/***/ "./front/assets/js/components/log-view/event-handlers.js"
/*!***************************************************************!*\
  !*** ./front/assets/js/components/log-view/event-handlers.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LogViewerEventHandlers: () => (/* binding */ LogViewerEventHandlers)
/* harmony export */ });
/* harmony import */ var bootstrap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
/* harmony import */ var _notification_utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./notification-utils.js */ "./front/assets/js/components/log-view/notification-utils.js");
/* harmony import */ var _constants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants.js */ "./front/assets/js/components/log-view/constants.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global $ */




/**
 * Event handlers for log viewer
 */
class LogViewerEventHandlers {
  constructor(table, apiService, uiUtils) {
    this.table = table;
    this.api = apiService;
    this.ui = uiUtils;
  }

  /**
   * Initialize event handlers
   */
  init() {
    this.bindDynamicEventHandlers();
    this.bindStaticEventHandlers();
  }

  /**
   * Bind event handlers for dynamically created elements
   */
  bindDynamicEventHandlers() {
    // SearchBuilder button click
    $('.search-builder-btn').on('click', function () {
      // Toggle searchBuilder visibility
      $('.dtsb-searchBuilder').toggle();

      // If there's only one child, trigger the button click
      if ($('.dtsb-group').children().length === 1) {
        $('.dtsb-group button').trigger('click');
      }
    });
  }

  /**
   * Bind event handlers for static elements
   */
  bindStaticEventHandlers() {
    const self = this;

    // Refresh log button
    $('.refresh-log').on('click', async function () {
      await self.api.updateLogs(() => self.ui.animateRefreshButton());
    });

    // Table XHR event
    $('#dbg_lv_log-table').on('xhr.dt', function (e, settings, json, xhr) {
      if (json.info) {
        const parent = $('.table-wrapper');
        const element = parent.find('.log-viewer-info');
        if (element.length > 0) {
          $(element).text(json.info);
        } else {
          $(parent).prepend(`<div class="alert alert-warning log-viewer-info" role="alert">${json.info}</div>`);
        }
      }
    });

    // Toggle debug mode checkbox
    $('#dbg_lv_toggle_debug_mode').on('change', async function () {
      await self.api.toggleDebugMode($(this).is(':checked'));
    });

    // Toggle debug scripts checkbox
    $('#dbg_lv_toggle_debug_scripts').on('change', async function () {
      await self.api.toggleDebugScripts($(this).is(':checked'));
    });

    // Toggle log in file checkbox
    $('#dbg_lv_toggle_log_in_file').on('change', async function () {
      const isChecked = $(this).is(':checked');
      if (isChecked) {
        self.autoEnableDebugMode();
      }
      await self.api.toggleLogInFile(isChecked);
    });

    // Toggle display errors checkbox
    $('#dbg_lv_toggle_display_errors').on('change', async function () {
      const checkbox = $(this);
      const isChecked = checkbox.is(':checked');
      if (isChecked) {
        const modalElement = $('#display-errors-warning-modal');
        const modal = new bootstrap__WEBPACK_IMPORTED_MODULE_0__.Modal(modalElement[0]); // Use imported Modal class

        $('#cancel-display-errors').off('click').on('click', function () {
          checkbox.prop('checked', false);
          modal.hide();
        });
        $('#confirm-display-errors').off('click').on('click', async function () {
          modal.hide();
          self.autoEnableDebugMode();
          await self.api.toggleDisplayErrors(true);
        });
        modal.show();
        return false;
      }
      await self.api.toggleDisplayErrors(false);
    });

    // Clear log button
    $(document).on('click', '.clear-log', async function () {
      await self.api.clearLog();
    });

    // Download log button
    $(document).on('click', '.download-log', function () {
      self.api.downloadLog();
    });

    // Email alerts form
    $('#dbg_lv_log_viewer_alerts_form').on('submit', async function (e) {
      e.preventDefault();
      const form = $('#dbg_lv_log_viewer_alerts_form');
      const action = 'dbg_lv_change_log_viewer_alerts_status';
      await (0,_notification_utils_js__WEBPACK_IMPORTED_MODULE_1__.updateEmailNotifications)(form, action);
      if (form.find('input[type="submit"]')?.val() === 'Enable') {
        self.autoEnableDebugMode();
        self.autoEnableDebugLog();
      }
    });

    // Update mode radio buttons
    $('input[name="UpdatesModeRadioOptions"]').on('change', async function () {
      const selectedMode = $('input[name="UpdatesModeRadioOptions"]:checked').val();

      // Update pill-radio active class
      $('input[name="UpdatesModeRadioOptions"]').closest('.pill-radio').removeClass('active');
      $(this).closest('.pill-radio').addClass('active');
      await self.api.changeLogsUpdateMode(selectedMode);
      if (selectedMode === 'AUTO') {
        self.ui.stopLiveUpdateLogs(); // Reset old timer
        self.ui.startLiveUpdateLogs(() => self.api.updateLogs());
      } else {
        self.ui.stopLiveUpdateLogs();
      }
    });

    // Datetime format radio buttons
    $('input[name="DatetimeFormatRadioOptions"]').on('change', async function () {
      const selectedFormat = $('input[name="DatetimeFormatRadioOptions"]:checked').val();

      // Update pill-radio active class
      $('input[name="DatetimeFormatRadioOptions"]').closest('.pill-radio').removeClass('active');
      $(this).closest('.pill-radio').addClass('active');
      await self.api.changeDatetimeFormat(selectedFormat);

      // Update the frontend setting for immediate UI updates
      dbg_lv_backend_data.datetime_format = selectedFormat;

      // Manage relative time updates based on the new format
      if (selectedFormat === _constants_js__WEBPACK_IMPORTED_MODULE_2__.DATETIME_FORMAT_RELATIVE) {
        self.ui.startRelativeTimeUpdates();
      } else {
        self.ui.stopRelativeTimeUpdates();
      }

      // Invalidate datetime column cache and redraw to show the new format
      self.table.cells(null, 2).invalidate().draw(false);
    });

    // Timezone dropdown
    $('#dbg_lv_timezone').on('change', async function () {
      const selectedTimezone = $(this).val();
      await self.api.changeTimezone(selectedTimezone);

      // Clear table and reload data from server to get re-formatted datetime with new timezone
      self.table.clear();
      await self.api.fillInitialData();
    });

    // Auto refresh interval dropdown
    $('#dbg_lv_auto_refresh_interval').on('change', async function () {
      const interval = parseInt($(this).val(), 10);
      await self.api.changeAutoRefreshInterval(interval);

      // Restart live updates with the new interval if in AUTO mode
      const currentMode = $('input[name="UpdatesModeRadioOptions"]:checked').val();
      if (currentMode === 'AUTO') {
        self.ui.stopLiveUpdateLogs();
        self.ui.startLiveUpdateLogs(() => self.api.updateLogs());
      }
    });

    // Group duplicates toggle (lives in Settings tab)
    $('#GroupEntriesToggle').on('change', async function () {
      const isGrouped = $(this).is(':checked');
      $(this).siblings('.toggle-label').text(isGrouped ? dbg_lv_backend_data.phrases?.enabled || 'Enabled' : dbg_lv_backend_data.phrases?.disabled || 'Disabled');
      await self.api.changeGroupEntries(isGrouped);

      // Show/hide Count column (index 3)
      self.table.column(3).visible(isGrouped);

      // Reload table with new grouping mode
      self.table.clear().draw();
      await self.api.fillInitialData();
    });
  }

  /**
   * Auto enable debug mode
   */
  autoEnableDebugMode() {
    const target = $('#dbg_lv_toggle_debug_mode');
    if (target.is(':checked')) {
      return;
    }

    // Simulate change event
    target.prop('checked', true).trigger('change');
  }

  /**
   * Auto enable debug log
   */
  autoEnableDebugLog() {
    const target = $('#dbg_lv_toggle_log_in_file');
    if (target.is(':checked')) {
      return;
    }

    // Simulate change event
    target.prop('checked', true).trigger('change');
  }
}

/***/ },

/***/ "./front/assets/js/components/log-view/index.js"
/*!******************************************************!*\
  !*** ./front/assets/js/components/log-view/index.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* harmony import */ var _table_config_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./table-config.js */ "./front/assets/js/components/log-view/table-config.js");
/* harmony import */ var _api_service_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./api-service.js */ "./front/assets/js/components/log-view/api-service.js");
/* harmony import */ var _ui_utils_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ui-utils.js */ "./front/assets/js/components/log-view/ui-utils.js");
/* harmony import */ var _event_handlers_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./event-handlers.js */ "./front/assets/js/components/log-view/event-handlers.js");
/* harmony import */ var _notification_utils_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./notification-utils.js */ "./front/assets/js/components/log-view/notification-utils.js");
/* harmony import */ var _help_scout_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../help-scout.js */ "./front/assets/js/components/help-scout.js");
/* harmony import */ var _alert_handler_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./alert-handler.js */ "./front/assets/js/components/log-view/alert-handler.js");
/* harmony import */ var _log_popover_renderer_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../log-popover-renderer.js */ "./front/assets/js/components/log-popover-renderer.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery, dbg_lv_backend_data, posthog */









(async $ => {
  (0,_help_scout_js__WEBPACK_IMPORTED_MODULE_6__.initHelpScoutBeacon)('055b8c26-4e0e-4352-a0f2-7e6f3e7c3336');

  // !(function (t, e) { let o, n, p, r; e.__SV || (window.posthog = e, e._i = [], e.init = function (i, s, a) { function g (t, e) { const o = e.split('.'); o.length == 2 && (t = t[o[0]], e = o[1]), t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } } (p = t.createElement('script')).type = 'text/javascript', p.crossOrigin = 'anonymous', p.async = !0, p.src = s.api_host.replace('.i.posthog.com', '-assets.i.posthog.com') + '/static/array.js', (r = t.getElementsByTagName('script')[0]).parentNode.insertBefore(p, r); let u = e; for (void 0 !== a ? u = e[a] = [] : a = 'posthog', u.people = u.people || [], u.toString = function (t) { let e = 'posthog'; return a !== 'posthog' && (e += '.' + a), t || (e += ' (stub)'), e }, u.people.toString = function () { return u.toString(1) + '.people (stub)' }, o = 'init Ie Ts Ms Ee Es Rs capture Ge calculateEventProperties Os register register_once register_for_session unregister unregister_for_session js getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey canRenderSurveyAsync identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty Ds Fs createPersonProfile Ls Ps opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing Cs debug I As getPageViewId captureTraceFeedback captureTraceMetric'.split(' '), n = 0; n < o.length; n++)g(u, o[n]); e._i.push([i, s, a]) }, e.__SV = 1) }(document, window.posthog || []))
  // posthog.init('phc_pYVBt9N6AieVgFXoQHj27wq3PwHLeujCZrNlXKSbYLv', {
  //   api_host: 'https://eu.i.posthog.com',
  //   defaults: '2025-05-24',
  //   person_profiles: 'always'
  // })

  const dataTableConfig = (0,_table_config_js__WEBPACK_IMPORTED_MODULE_1__.createDataTableConfig)();
  const table = $('#dbg_lv_log-table').DataTable(dataTableConfig);

  // Sync Count column visibility with the saved group_entries preference
  const isGroupingOnInit = dbg_lv_backend_data.group_entries !== '0';
  table.column(3).visible(isGroupingOnInit);
  $('.right-subheader .search-input').on('input', function () {
    const searchValue = $(this).val().trim();
    if (searchValue) {
      table.search(searchValue).draw();
    } else {
      table.search('').draw();
    }
  });
  const apiService = new _api_service_js__WEBPACK_IMPORTED_MODULE_2__.LogViewerApiService(table);
  const uiUtils = new _ui_utils_js__WEBPACK_IMPORTED_MODULE_3__.LogViewerUI(table);
  const eventHandlers = new _event_handlers_js__WEBPACK_IMPORTED_MODULE_4__.LogViewerEventHandlers(table, apiService, uiUtils);
  const alertHandler = new _alert_handler_js__WEBPACK_IMPORTED_MODULE_7__.AlertHandler();
  (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.initScrollToTopButton)();
  alertHandler.init();
  await apiService.fillInitialData();
  const $logPathIcons = $('.log-path-icons');
  if ($logPathIcons.length) {
    (0,_log_popover_renderer_js__WEBPACK_IMPORTED_MODULE_8__.initLogPanels)($logPathIcons);
  }
  $('#dbg_lv_log-table_processing').remove();

  // Only check if debug log is publicly accessible if the alert hasn't been dismissed
  if (!alertHandler.isCurrentlyDismissed()) {
    const result = await apiService.isDebugLogPubliclyAccessible();
    if (result.success && result.data.is_public) {
      if (result.data.info) {
        if ($logPathIcons.length) {
          (0,_log_popover_renderer_js__WEBPACK_IMPORTED_MODULE_8__.appendPublicAccessWarning)($logPathIcons, result.data.info);
        }
      }
    }
  }
  uiUtils.setupTimeFilter();
  eventHandlers.init();
  uiUtils.startRelativeTimeUpdates();
  await (0,_notification_utils_js__WEBPACK_IMPORTED_MODULE_5__.initEmailNotificationsForm)($('#dbg_lv_log_viewer_alerts_form'));
  if (dbg_lv_backend_data.log_updates_mode === 'AUTO') {
    uiUtils.startLiveUpdateLogs(() => apiService.updateLogs(() => uiUtils.animateRefreshButton()));
  }
})(jQuery);

/***/ },

/***/ "./front/assets/js/components/log-view/notification-utils.js"
/*!*******************************************************************!*\
  !*** ./front/assets/js/components/log-view/notification-utils.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initEmailNotificationsForm: () => (/* binding */ initEmailNotificationsForm),
/* harmony export */   updateEmailNotifications: () => (/* binding */ updateEmailNotifications)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* harmony import */ var _email_recipients_manager_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./email-recipients-manager.js */ "./front/assets/js/components/log-view/email-recipients-manager.js");
/* harmony import */ var _schedule_manager_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../schedule-manager.js */ "./front/assets/js/components/schedule-manager.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery, ajaxurl, dbg_lv_backend_data */



let emailRecipientsManager = null;
let alertScheduleManager = null;

/**
 * Updates email notification settings
 * @param {jQuery} form - The alerts form
 * @param {string} action - The AJAX action to perform
 * @returns {Promise<void>}
 */
async function updateEmailNotifications(form, action) {
  const notificationStatus = form.attr('data-alerts-enabled');
  const recurrenceField = form.find('select[name="recurrence"]');
  const testEmailBtn = form.find('#send-test-email-btn');
  const submitButton = form.find('input[type="submit"]');
  const errorLevelCheckboxes = form.find('input[name="levels[]"]');

  // Validate emails using the manager
  const validation = emailRecipientsManager.validate();
  if (!validation.isValid) {
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(validation.errors.join(', '), 'warning');
    return;
  }
  const emails = emailRecipientsManager.getEmails();
  if (emails.length === 0) {
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('email_is_not_specified'), 'warning');
    return;
  }

  // Get selected error levels - only from enabled checkboxes (non-pro features)
  const selectedLevels = form.find('input[name="levels[]"]:checked:not(.pro-disabled)').map(function () {
    return jQuery(this).val();
  }).get();

  // Check if togglers are disabled for free users
  const hasProDisabledTogglers = form.find('.togglers-group.pro-disabled').length > 0;

  // For free users with pro-disabled togglers, skip level validation
  // They'll get default error levels from the backend
  if (notificationStatus === 'false' && !hasProDisabledTogglers && selectedLevels.length === 0) {
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('please_select_error_level'), 'warning');
    return;
  }
  submitButton.attr('disabled', 'disabled');
  try {
    if (notificationStatus === 'true') {
      const response = await jQuery.post(ajaxurl, {
        action,
        status: 'disable',
        emails,
        levels: selectedLevels,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data || (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('request_error'));
      }
      form.attr('data-alerts-enabled', 'false');
      submitButton.removeClass('disable btn-outline-danger').addClass('enable btn-primary').val('Enable');
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('notifications_disabled'), 'success');
      const userEmailResponse = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_get_current_user_email',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!userEmailResponse.success) {
        throw new Error(userEmailResponse.data || (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('request_error'));
      }
      emailRecipientsManager.setEnabled(true);
      recurrenceField.prop('disabled', false);
      testEmailBtn.prop('disabled', false).closest('.alert-test-card').show();

      // Hide next-run banner when alerts are disabled
      alertScheduleManager.hide();

      // Only enable error level checkboxes if they're not pro-disabled
      errorLevelCheckboxes.each(function () {
        const checkbox = jQuery(this);
        const isProDisabled = checkbox.closest('.togglers-group').hasClass('pro-disabled');
        if (!isProDisabled) {
          checkbox.prop('disabled', false);
        }
      });
    } else {
      const response = await jQuery.post(ajaxurl, {
        action,
        status: 'enable',
        recurrence: recurrenceField?.val(),
        emails,
        levels: selectedLevels,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data || (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('request_error'));
      }
      emailRecipientsManager.setEnabled(false);
      recurrenceField.prop('disabled', true);
      testEmailBtn.prop('disabled', true).closest('.alert-test-card').hide();

      // Show next-run banner after enabling
      await alertScheduleManager.updateScheduleDisplay();

      // Always disable error level checkboxes when notifications are enabled
      errorLevelCheckboxes.prop('disabled', true);
      submitButton.removeClass('enable btn-primary').addClass('disable btn-outline-danger').val((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('disable'));
      form.attr('data-alerts-enabled', 'true');
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('notifications_enabled'), 'success');
    }
  } catch (error) {
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
  }
  submitButton.prop('disabled', false);
}

/**
 * Initializes the email alerts form
 * @param {jQuery} form - The form to initialize
 * @returns {Promise<void>}
 */
async function initEmailNotificationsForm(form) {
  if (!form || !form.length) {
    console.warn((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('email_alerts_form_not_found'));
    return;
  }
  emailRecipientsManager = new _email_recipients_manager_js__WEBPACK_IMPORTED_MODULE_1__.EmailRecipientsManager();
  alertScheduleManager = new _schedule_manager_js__WEBPACK_IMPORTED_MODULE_2__.ScheduleManager('alerts-next-run', 'dbg_lv_get_alert_schedule');
  const recurrenceField = form.find('select[name="recurrence"]');
  const testEmailBtn = form.find('#send-test-email-btn');
  const submitButton = form.find('input[type="submit"]');
  const errorLevelCheckboxes = form.find('input[name="levels[]"]');

  // Elements are already disabled in HTML, just add loading visual state
  form.addClass('loading');
  try {
    if (form.attr('data-alerts-enabled') === 'true') {
      // Remove loading state but keep form disabled since alerts are enabled
      form.removeClass('loading');
      emailRecipientsManager.setEnabled(false);
      recurrenceField.prop('disabled', true);
      testEmailBtn.prop('disabled', true).closest('.alert-test-card').hide();
      errorLevelCheckboxes.prop('disabled', true);
      submitButton.prop('disabled', false).removeClass('enabled btn-primary').addClass('disable btn-outline-danger').val((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('disable'));
      await alertScheduleManager.updateScheduleDisplay();
    } else {
      // For disabled alerts, check if we have existing emails from the template
      const existingEmails = emailRecipientsManager.getEmails();

      // If no existing emails, fetch the current user's email as default
      if (existingEmails.length === 0) {
        const response = await jQuery.post(ajaxurl, {
          action: 'dbg_lv_get_current_user_email',
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        });
        if (!response.success) {
          throw new Error(response.data || (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('request_error'));
        }
        // Set the current user's email as the default
        emailRecipientsManager.setEmails([response.data]);
      }

      // Enable all form elements since alerts are disabled
      form.removeClass('loading');
      emailRecipientsManager.setEnabled(true);
      recurrenceField.prop('disabled', false);
      testEmailBtn.prop('disabled', false).closest('.alert-test-card').show();

      // Only enable error level checkboxes if they're not pro-disabled
      errorLevelCheckboxes.each(function () {
        const checkbox = jQuery(this);
        const isProDisabled = checkbox.closest('.togglers-group').hasClass('pro-disabled');
        if (!isProDisabled) {
          checkbox.prop('disabled', false);
        }
      });
      submitButton.prop('disabled', false).removeClass('disable btn-outline-danger').addClass('enable btn-primary').val((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('enable'));
    }
  } catch (error) {
    // Remove loading state and enable basic form functionality on error
    form.removeClass('loading');
    submitButton.prop('disabled', false).val((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('enable'));
    (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
  }
  testEmailBtn.on('click', async function () {
    const emails = emailRecipientsManager.getEmails();
    if (emails.length === 0) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('email_is_not_specified'), 'warning');
      return;
    }
    testEmailBtn.prop('disabled', true).addClass('loading');
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_send_test_alert_email',
        emails,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
    } finally {
      testEmailBtn.prop('disabled', false).removeClass('loading');
    }
  });
}

/***/ },

/***/ "./front/assets/js/components/log-view/renderers.js"
/*!**********************************************************!*\
  !*** ./front/assets/js/components/log-view/renderers.js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   convertToTimestamp: () => (/* binding */ convertToTimestamp),
/* harmony export */   renderCount: () => (/* binding */ renderCount),
/* harmony export */   renderDatetime: () => (/* binding */ renderDatetime),
/* harmony export */   renderDescription: () => (/* binding */ renderDescription),
/* harmony export */   renderLogTypeBadge: () => (/* binding */ renderLogTypeBadge)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* harmony import */ var _constants_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./constants.js */ "./front/assets/js/components/log-view/constants.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");



/**
 * Renders a badge for log type with appropriate styling
 */
function renderLogTypeBadge(data) {
  const typeClasses = {
    Notice: 'notice-bg',
    Warning: 'warning-bg',
    Fatal: 'fatal-bg',
    Database: 'database-bg',
    Parse: 'parse-bg',
    Deprecated: 'deprecated-bg',
    Custom: 'custom-bg'
  };
  const className = typeClasses[data] || 'bg-secondary';
  return `<span class="badge ${className}">${data}</span>`;
}

/**
 * Renders description with collapsible stack trace if available
 */
function renderDescription(description) {
  const {
    text,
    stack_trace: stackTrace
  } = description;
  if (!stackTrace) return text;

  // Generate unique ID with only alphanumeric characters
  const collapseId = 'stack-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
  return `
    <div class="error-description">${text}</div>
    <a class="stack-trace" data-target="#${collapseId}">
      <i class="fa-solid fa-code"></i>
      ${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('stack_trace')}
    </a>
    <div id="${collapseId}" class="dlv-stack-trace" style="display: none;">
      <div class="copy-stack-btn" title="Copy to clipboard">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
          <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg>
      </div>
      <pre>${stackTrace}</pre>
    </div>
  `;
}
function convertToTimestamp(datetimeString) {
  return new Date(datetimeString).getTime();
}
function renderDatetime(data, type, row) {
  if (type === 'display') {
    const datetimeFormat = window.dbg_lv_backend_data?.datetime_format || _constants_js__WEBPACK_IMPORTED_MODULE_1__.DATETIME_FORMAT_ABSOLUTE;
    if (datetimeFormat === _constants_js__WEBPACK_IMPORTED_MODULE_1__.DATETIME_FORMAT_RELATIVE) {
      return formatRelativeTime(data);
    }
    return data;
  }
  return convertToTimestamp(data);
}

/**
 * Renders the occurrence count for grouped entries.
 */
function renderCount(data, type) {
  const count = data || 1;
  if (type !== 'display') {
    return count;
  }
  if (count <= 1) {
    return `<span class="dlv-count-badge dlv-count-single">${count}</span>`;
  }
  return `<span class="dlv-count-badge dlv-count-multiple">${count}</span>`;
}
function formatRelativeTime(datetimeString) {
  const logDate = new Date(datetimeString);
  const now = new Date();

  // Handle invalid dates
  if (isNaN(logDate.getTime())) {
    return datetimeString; // Return original string if parsing fails
  }
  const diffMs = now - logDate;

  // If the log entry is in the future, return original datetime
  if (diffMs < 0) {
    return datetimeString;
  }
  const diffSeconds = Math.floor(diffMs / 1000);

  // Use the translation function for consistency
  const phrases = window.dbg_lv_backend_data?.phrases || {};

  // Less than 30 seconds = "just now"
  if (diffSeconds < 30) {
    return phrases.just_now || 'just now';
  }

  // 30 seconds to 1 minute = "1 minute ago"
  if (diffSeconds < 60) {
    return phrases.one_minute_ago || '1 minute ago';
  }

  // 1 to 2 minutes = "2 minutes ago"
  if (diffSeconds < 120) {
    return phrases.two_minutes_ago || '2 minutes ago';
  }

  // 2 to 5 minutes = "5 minutes ago"
  if (diffSeconds < 300) {
    return phrases.five_minutes_ago || '5 minutes ago';
  }

  // 5 to 10 minutes = "10 minutes ago"
  if (diffSeconds < 600) {
    return phrases.ten_minutes_ago || '10 minutes ago';
  }

  // 10 to 15 minutes = "15 minutes ago"
  if (diffSeconds < 900) {
    return phrases.fifteen_minutes_ago || '15 minutes ago';
  }

  // 15 to 30 minutes = "30 minutes ago"
  if (diffSeconds < 1800) {
    return phrases.thirty_minutes_ago || '30 minutes ago';
  }

  // 30 minutes to 1 hour = "1 hour ago"
  if (diffSeconds < 3600) {
    return phrases.one_hour_ago || '1 hour ago';
  }

  // 1 to 2 hours = "2 hours ago"
  if (diffSeconds < 7200) {
    return phrases.two_hours_ago || '2 hours ago';
  }

  // 2 to 3 hours = "3 hours ago"
  if (diffSeconds < 10800) {
    return phrases.three_hours_ago || '3 hours ago';
  }

  // 3 to 6 hours = "6 hours ago"
  if (diffSeconds < 21600) {
    return phrases.six_hours_ago || '6 hours ago';
  }

  // 6 to 12 hours = "12 hours ago"
  if (diffSeconds < 43200) {
    return phrases.twelve_hours_ago || '12 hours ago';
  }

  // 12 to 24 hours = "1 day ago"
  if (diffSeconds < 86400) {
    return phrases.one_day_ago || '1 day ago';
  }

  // 1 to 2 days = "2 days ago"
  if (diffSeconds < 172800) {
    return phrases.two_days_ago || '2 days ago';
  }

  // 2 to 3 days = "3 days ago"
  if (diffSeconds < 259200) {
    return phrases.three_days_ago || '3 days ago';
  }

  // 3 to 7 days = "1 week ago"
  if (diffSeconds < 604800) {
    return phrases.one_week_ago || '1 week ago';
  }

  // 1 to 2 weeks = "2 weeks ago"
  if (diffSeconds < 1209600) {
    return phrases.two_weeks_ago || '2 weeks ago';
  }

  // 2 weeks to 1 month = "1 month ago"
  if (diffSeconds < 2592000) {
    return phrases.one_month_ago || '1 month ago';
  }

  // 1 to 2 months = "2 months ago"
  if (diffSeconds < 5184000) {
    return phrases.two_months_ago || '2 months ago';
  }

  // 2 to 3 months = "3 months ago"
  if (diffSeconds < 7776000) {
    return phrases.three_months_ago || '3 months ago';
  }

  // 3 to 6 months = "6 months ago"
  if (diffSeconds < 15552000) {
    return phrases.six_months_ago || '6 months ago';
  }

  // 6 months to 1 year = "1 year ago"
  if (diffSeconds < 31536000) {
    return phrases.one_year_ago || '1 year ago';
  }

  // More than 1 year - calculate years
  const years = Math.floor(diffSeconds / 31536000);
  if (years === 1) {
    return phrases.one_year_ago || '1 year ago';
  } else {
    return (phrases.years_ago || '%d years ago').replace('%d', years);
  }
}

/**
 * Initialize jQuery animations for log view components
 * Call this function after log entries are rendered
 */

/* global $ */
$(document).ready(function ($) {
  // Handle stack trace toggle links
  $(document).on('click', '.stack-trace', function (e) {
    e.preventDefault();
    const $target = $($(this).data('target'));
    if ($target.is(':visible')) {
      $target.slideUp(300, () => {
        $(this).toggleClass('active');
      });
      $(this).html(`
        <i class="fa-solid fa-code"></i>
        ${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('stack_trace')}`);
    } else {
      $target.slideDown(300);
      $(this).toggleClass('active');
      $(this).html((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('hide_stack_trace'));
    }
  });

  // Generic copy to clipboard function
  function copyToClipboard($button, text) {
    navigator.clipboard.writeText(text).then(() => {
      const originalSvg = $button.html();

      // Change to checkmark for feedback
      $button.html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5"></path></svg>');

      // Reset after a short delay
      setTimeout(() => {
        $button.html(originalSvg);
      }, 1500);
    });
  }

  // Handle copy to clipboard for stack traces
  $(document).on('click', '.copy-stack-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const stackTrace = $(this).siblings('pre').text();
    copyToClipboard($(this), stackTrace);
  });

  // Handle copy path to clipboard
  $(document).on('click', '.copy-path-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const path = $(this).siblings('.path-segment').text();
    copyToClipboard($(this), path);
  });

  // console.log('DataTable initialized')
  // $('.dtsb-clearAll').removeClass('btn-secondary').addClass('btn-sm btn-danger')
  // $('.dtsb-add').removeClass('btn-secondary').addClass('btn-sm btn-success')
  // $('.dtsb-delete').removeClass('btn-secondary').addClass('btn-sm btn-outline-danger')
});

/***/ },

/***/ "./front/assets/js/components/log-view/table-config.js"
/*!*************************************************************!*\
  !*** ./front/assets/js/components/log-view/table-config.js ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createDataTableConfig: () => (/* binding */ createDataTableConfig)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* harmony import */ var _renderers_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./renderers.js */ "./front/assets/js/components/log-view/renderers.js");
/* harmony import */ var _ui_utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ui-utils.js */ "./front/assets/js/components/log-view/ui-utils.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global $, MutationObserver */




/**
 * Creates and returns the DataTable configuration object
 */
function createDataTableConfig(initCallbacks) {
  return {
    serverSide: false,
    stateSave: true,
    ordering: true,
    retrieve: true,
    bSort: true,
    order: [[0, 'desc']],
    // Sort by timestamp desc by default
    processing: true,
    language: {
      processing: `<div class="dlv-spinner-wrap"><span class="dlv-spinner"></span><span class="dlv-spinner-label">${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('loading_in_process')}</span></div>`,
      search: '_INPUT_',
      lengthMenu: '_MENU_',
      emptyTable: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('empty_log_table'),
      zeroRecords: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('no_matching_records')
    },
    pageLength: 10,
    layout: {
      topStart: null,
      // remove pageLength from top default position
      topEnd: null,
      // remove search from top default position
      top8Start: ['pageLength', {
        buttons: [{
          extend: 'colvis',
          className: 'column-visibility-btn',
          text: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('show-hide')
        }, {
          extend: 'csv',
          className: 'csv-export-btn d-none',
          // Hidden, we'll trigger it from the dropdown
          filename: 'debug-log-export',
          exportOptions: {
            format: {
              body: data => {
                // Clean up HTML tags from the data
                return $('<div>').html(data).text();
              }
            }
          }
        }]
      }, $(`
          <div class="time-filter">
            <div class="datetime-range-buttons btn-group">
              <button class="btn btn-sm btn-outline-primary" value="5m">5m</button>
              <button class="btn btn-sm btn-outline-primary" value="30m">30m</button>
              <button class="btn btn-sm btn-outline-primary" value="1h">1h</button>
              <button class="btn btn-sm btn-outline-primary" value="12h">12h</button>
              <button class="btn btn-sm btn-outline-primary active" value="all">All</button>
              ${(0,_ui_utils_js__WEBPACK_IMPORTED_MODULE_2__.isPremium)() ? '<button class="btn btn-sm btn-outline-primary custom-range-btn" value="custom">Custom</button>' : `<div class="position-relative d-inline-block">
                    <button class="btn btn-sm btn-outline-primary custom-range-btn pro-disabled" value="custom" disabled>Custom</button>
                    <span class="pro-badge freemius-checkout-trigger" title="Upgrade to Pro">Pro</span>
                </div>`}
            </div>
          </div>`), $(`<div class="d-flex ms-4 advanced-filter">
            <button class="btn btn-sm btn-outline-primary search-builder-btn" style="height: 30px; border:none">
              <i class="fa fa-filter"></i>
              <span class="filter-text">${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('filter')}</span>
              <span class="filter-counter"></span>
            </button>
          </div>`)],
      top8End: [{
        search: {}
      }, $(`<div class="dropdown more-dropdown">
          <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <span>${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('more')}</span>
          </button>
          <ul class="dropdown-menu more-menu" aria-labelledby="dropdownMenuButton">
            ${(0,_ui_utils_js__WEBPACK_IMPORTED_MODULE_2__.createDropdownMenuItems)()}
          </ul>
        </div>`), $(`<button class="btn btn-primary refresh-log" title="${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('refresh_log')}"><i class="fa fa-solid fa-arrows-rotate"></i></button>`)],
      top7: 'searchBuilder'
    },
    columns: [{
      data: 'timestamp',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('timestamp') || 'Timestamp',
      visible: false,
      render: function (data, type, row) {
        // If timestamp is missing, calculate it from datetime
        return data || (0,_renderers_js__WEBPACK_IMPORTED_MODULE_1__.convertToTimestamp)(row.datetime);
      }
    }, {
      data: 'type',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('type') || 'Type',
      orderable: false,
      render: _renderers_js__WEBPACK_IMPORTED_MODULE_1__.renderLogTypeBadge
    }, {
      data: 'datetime',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('last_seen') || 'Last seen',
      className: 'datetime',
      width: '10%',
      render: _renderers_js__WEBPACK_IMPORTED_MODULE_1__.renderDatetime
    }, {
      data: 'count',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('count') || 'Count',
      className: 'count-col',
      width: '5%',
      render: _renderers_js__WEBPACK_IMPORTED_MODULE_1__.renderCount
    }, {
      data: 'description',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('description') || 'Description',
      className: 'description ph-no-capture',
      orderable: false,
      render: _renderers_js__WEBPACK_IMPORTED_MODULE_1__.renderDescription,
      width: '50%'
    }, {
      data: 'file',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('file') || 'File',
      className: 'file ph-no-capture',
      orderable: false,
      width: '25%'
    }, {
      data: 'line',
      title: (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('line') || 'Line',
      className: 'line',
      orderable: false,
      width: '15%'
    }],
    autoWidth: false,
    stateSaveParams: function (settings, data) {
      // Don't persist Count column (index 3) visibility — it's controlled by the grouping setting
      if (data.columns && data.columns[3]) {
        delete data.columns[3].visible;
      }
    },
    initComplete: function () {
      if (initCallbacks && typeof initCallbacks.onInitComplete === 'function') {
        initCallbacks.onInitComplete();
      }
      const table = this.api();
      $(document).on('click', '.export-csv-btn', function (e) {
        e.preventDefault();
        table.button('.csv-export-btn').trigger();
      });
      const updateFilterCountHandler = () => (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.updateFilterCount)(table);
      table.on('searchBuilder-rebuild', updateFilterCountHandler);
      table.on('searchBuilder-redraw', updateFilterCountHandler);
      table.on('draw', updateFilterCountHandler);
      const searchBuilderObserver = new MutationObserver(() => (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.updateFilterCount)(table));
      const searchBuilderContainer = document.querySelector('.dtsb-searchBuilder');
      if (searchBuilderContainer) {
        searchBuilderObserver.observe(searchBuilderContainer, {
          childList: true,
          subtree: true
        });
      }
      setTimeout(() => (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.updateFilterCount)(table), 500);
    }
  };
}

/***/ },

/***/ "./front/assets/js/components/log-view/ui-utils.js"
/*!*********************************************************!*\
  !*** ./front/assets/js/components/log-view/ui-utils.js ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LogViewerUI: () => (/* binding */ LogViewerUI),
/* harmony export */   createDropdownMenuItems: () => (/* binding */ createDropdownMenuItems),
/* harmony export */   getFreemiusCheckoutHandler: () => (/* binding */ getFreemiusCheckoutHandler),
/* harmony export */   isPremium: () => (/* binding */ isPremium),
/* harmony export */   openFreemiusCheckout: () => (/* binding */ openFreemiusCheckout),
/* harmony export */   openFreemiusPricing: () => (/* binding */ openFreemiusPricing),
/* harmony export */   showFeaturesModal: () => (/* binding */ showFeaturesModal)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* harmony import */ var _constants_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./constants.js */ "./front/assets/js/components/log-view/constants.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global $, dbg_lv_backend_data, FS */


const isPremium = () => window.dbg_lv_backend_data?.is_premium || window.dbg_lv_freemius_data?.is_premium || false;

/**
 * Initialize and return Freemius checkout handler
 */
const getFreemiusCheckoutHandler = () => {
  if (typeof FS === 'undefined' || !FS.Checkout) {
    console.warn('Freemius checkout not available');
    return null;
  }
  const freemiusData = window.dbg_lv_freemius_data || {};
  return new FS.Checkout({
    product_id: freemiusData.product_id || '17350',
    plan_id: freemiusData.plan_id || '29848',
    public_key: freemiusData.public_key || 'pk_d456c712f16510d920c9f4ba4880a',
    image: freemiusData.image || ''
  });
};
const openFreemiusCheckout = () => {
  const handler = getFreemiusCheckoutHandler();
  if (!handler) {
    // Fallback to direct URL if Freemius SDK is not available
    const checkoutUrl = window.dbg_lv_backend_data?.checkout_url || window.dbg_lv_freemius_data?.checkout_url || '';
    if (checkoutUrl) {
      window.open(checkoutUrl, '_blank');
    }
    return;
  }

  // Open with options to show Freemius's native pricing selector
  handler.open({
    name: 'Debug Log Viewer',
    // Don't specify licenses/plan_id to let Freemius show all plans
    billing_cycle_selector: 'dropdown',
    // Show billing cycle options
    show_reviews: true,
    // Show customer reviews
    show_refund_badge: true,
    // Show money-back guarantee
    success: response => {
      console.log('Purchase completed:', response);
    }
  });
};

/**
 * Show quick features modal before checkout
 */
const showFeaturesModal = () => {
  const $modal = $('#dbg-lv-features-modal');
  if ($modal.length) {
    $modal.modal('show');
  } else {
    // Fallback if modal not available - go directly to checkout
    openFreemiusCheckout();
  }
};

/**
 * Open Freemius pricing/upgrade flow
 */
const openFreemiusPricing = () => {
  showFeaturesModal();
};

/**
 * Creates the dropdown menu items based on available features
 */
function createDropdownMenuItems() {
  let menuItems = `
    <li><a class="dropdown-item download-btn download-log" href="#">
      <i class="fa fa-download"></i>${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('download_log')}</a></li>
    <li><a class="dropdown-item clear-log-btn clear-log" href="#">
    <i class="fa fa-trash"></i>${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('clear_log')}</a></li>`;
  if (isPremium()) {
    menuItems += `
      <li><a class="dropdown-item export-csv-btn" href="#">
        <i class="fa fa-file-csv"></i>${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('export_csv')}
      </a></li>`;
  } else {
    /* <fs_free_only> */

    menuItems += `<li>
      <a class="dropdown-item pro-feature freemius-checkout-trigger" href="#" title="${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('upgrade_to_pro')}">
        <i class="fa fa-file-csv"></i>${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('export_csv')} 
        <span class="pro-badge">Pro</span>
      </a>
    </li>`;
    /* </fs_free_only> */
  }
  return menuItems;
}

// /**
//  * UI utilities for log viewer
//  */
class LogViewerUI {
  constructor(table) {
    this.table = table;
    this.logsUpdatesIcon = $('.refresh-log i');
    this.logsUpdateInterval = null;
  }

  /**
     * Animate refresh button
     */
  animateRefreshButton() {
    const icon = this.logsUpdatesIcon[0];

    // Remove existing class and force reflow to restart animation
    this.logsUpdatesIcon.removeClass('rotate-animation');
    this.logsUpdatesIcon.addClass('rotate-animation');

    // Clean up after animation completes
    icon?.addEventListener('animationend', () => {
      this.logsUpdatesIcon.removeClass('rotate-animation');
    }, {
      once: true
    });
  }

  /**
     * Start live update logs
     */
  startLiveUpdateLogs(updateLogsCallback) {
    const timeout = dbg_lv_backend_data.log_updates_interval * 1000;
    this.logsUpdateInterval = setInterval(updateLogsCallback, timeout);
  }

  /**
     * Stop live update logs
     */
  stopLiveUpdateLogs() {
    if (this.logsUpdateInterval) {
      clearInterval(this.logsUpdateInterval);
    }
  }

  /**
   * Setup time filter for DataTable
   */
  setupTimeFilter() {
    const table = this.table;
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      const filter = $('.datetime-range-buttons .btn.active').val(); // Get the active filter

      if (filter === 'all') {
        return true; // Show all rows
      }
      const now = new Date();
      const timestamp = new Date(Number(data[0])); // Assuming the first column is the timestamp
      if (isNaN(timestamp)) {
        // If timestamp parsing fails, exclude the row
        return false;
      }
      const timeDiff = now - timestamp; // Time difference in milliseconds

      if (filter === 'custom') {
        const fromDate = $('#custom-date-from').val();
        const toDate = $('#custom-date-to').val();
        if (!fromDate || !toDate) {
          return true; // Show all if dates not set
        }
        const from = new Date(fromDate).getTime();
        const to = new Date(toDate).getTime();
        const timestampMs = timestamp.getTime();
        return timestampMs >= from && timestampMs <= to;
      }
      return filter === '5m' && timeDiff <= 5 * 60 * 1000 || filter === '30m' && timeDiff <= 30 * 60 * 1000 || filter === '1h' && timeDiff <= 60 * 60 * 1000 || filter === '12h' && timeDiff <= 12 * 60 * 60 * 1000;
    });

    // Filter Button Click Event
    $('html').on('click', '.datetime-range-buttons .btn', function () {
      // Check if this is a pro feature and user doesn't have premium
      if ($(this).hasClass('pro-disabled') && !window.dbg_lv_backend_data?.is_premium) {
        // Show upgrade message or redirect to upgrade
        openFreemiusPricing();
        return false;
      }
      const filter = $(this).val();
      $('.datetime-range-buttons .btn').removeClass('active');
      $(this).addClass('active');
      if (filter === 'custom') {
        // Show custom date range inputs without relying on Bootstrap popover
        let $customContainer = $('.custom-date-range-dropdown');
        if ($customContainer.length === 0) {
          // Create the custom date range dropdown if it doesn't exist
          $customContainer = $(`
            <div class="custom-date-range-dropdown">
              <div class="date-range-content">
                <div class="date-input-group">
                  <label for="custom-date-from" class="form-label">From:</label>
                  <input type="datetime-local" id="custom-date-from" class="form-control form-control-sm" />
                </div>
                <div class="date-input-group">
                  <label for="custom-date-to" class="form-label">To:</label>
                  <input type="datetime-local" id="custom-date-to" class="form-control form-control-sm" />
                </div>
                <div class="date-actions">
                  <button type="button" class="btn btn-sm btn-primary apply-custom-range">Apply</button>
                  <button type="button" class="btn btn-sm btn-secondary cancel-custom-range">Cancel</button>
                </div>
              </div>
            </div>
          `);

          // Insert after the time filter buttons
          $('.time-filter').append($customContainer);
        }

        // Show the custom container
        $customContainer.show();

        // Always update max datetime to current time (timezone-aware)
        const now = new Date();
        const timezoneOffset = now.getTimezoneOffset() * 60000; // Convert to milliseconds
        const localNow = new Date(now.getTime() - timezoneOffset);
        const maxDateTime = localNow.toISOString().slice(0, 16);

        // Set default values if not set and apply timezone-aware max datetime
        const $fromInput = $('#custom-date-from');
        const $toInput = $('#custom-date-to');

        // Always update max attribute to prevent future date selection
        $fromInput.attr('max', maxDateTime);
        $toInput.attr('max', maxDateTime);
        if (!$fromInput.val() || !$toInput.val()) {
          const oneHourAgo = new Date(localNow.getTime() - 60 * 60 * 1000);
          $fromInput.val(oneHourAgo.toISOString().slice(0, 16));
          $toInput.val(maxDateTime);
        }
      } else {
        // Hide custom container if visible
        $('.custom-date-range-dropdown').hide();
        table.draw();
      }
    });

    // Custom date range apply button
    $('html').on('click', '.apply-custom-range', function () {
      const fromDate = $('#custom-date-from').val();
      const toDate = $('#custom-date-to').val();
      if (!fromDate || !toDate) {
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('select_both_dates') || 'Please select both from and to dates.', 'error');
        return;
      }
      const fromDateTime = new Date(fromDate);
      const toDateTime = new Date(toDate);
      const now = new Date();
      if (fromDateTime > toDateTime) {
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('invalid_date_range') || 'From date cannot be later than to date.', 'error');
        return;
      }

      // Check if any selected date is in the future
      if (fromDateTime > now || toDateTime > now) {
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('future_date_not_allowed') || 'Future dates are not allowed. Please select dates up to the current time.', 'error');
        return;
      }

      // Hide the custom dropdown
      $('.custom-date-range-dropdown').hide();

      // Keep the custom button active
      $('.datetime-range-buttons .btn').removeClass('active');
      $('.datetime-range-buttons .btn[value="custom"]').addClass('active');
      table.draw();
    });

    // Custom date range cancel button
    $('html').on('click', '.cancel-custom-range', function () {
      // Hide the custom dropdown
      $('.custom-date-range-dropdown').hide();

      // Reset to "All" filter
      $('.datetime-range-buttons .btn').removeClass('active');
      $('.datetime-range-buttons .btn[value="all"]').addClass('active');
      table.draw();
    });

    // Hide custom dropdown when clicking outside
    $(document).on('click', function (e) {
      const $customContainer = $('.custom-date-range-dropdown');
      const $customBtn = $('.custom-range-btn');
      if ($customContainer.is(':visible') && !$customBtn.is(e.target) && !$customContainer.is(e.target) && $customContainer.has(e.target).length === 0) {
        $customContainer.hide();
        // Reset to "All" filter
        $('.datetime-range-buttons .btn').removeClass('active');
        $('.datetime-range-buttons .btn[value="all"]').addClass('active');
        table.draw();
      }
    });
  }
  startRelativeTimeUpdates() {
    this.stopRelativeTimeUpdates();
    if (window.dbg_lv_backend_data?.datetime_format === _constants_js__WEBPACK_IMPORTED_MODULE_1__.DATETIME_FORMAT_RELATIVE) {
      this.relativeTimeInterval = setInterval(() => {
        if (window.dbg_lv_backend_data?.datetime_format === _constants_js__WEBPACK_IMPORTED_MODULE_1__.DATETIME_FORMAT_RELATIVE) {
          this.table.cells(null, 2).invalidate().draw(false);
        }
      }, 60000);
    }
  }
  stopRelativeTimeUpdates() {
    if (this.relativeTimeInterval) {
      clearInterval(this.relativeTimeInterval);
      this.relativeTimeInterval = null;
    }
  }
}
$(document).ready(function () {
  $(document).on('click', '.freemius-checkout-trigger', function (e) {
    e.preventDefault();
    e.stopPropagation();
    openFreemiusPricing();
  });

  // Handle upgrade button in features modal
  $(document).on('click', '#features-modal-upgrade-btn', function (e) {
    e.preventDefault();
    $('#dbg-lv-features-modal').modal('hide');
    // Small delay to let modal close animation finish
    setTimeout(() => openFreemiusCheckout(), 300);
  });
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

/***/ }

}]);