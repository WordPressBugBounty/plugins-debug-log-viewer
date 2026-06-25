"use strict";
(self["webpackChunkdebug_log_viewer"] = self["webpackChunkdebug_log_viewer"] || []).push([["front_assets_js_components_log-view_api-service_js"],{

/***/ "./front/assets/js/components/log-view/api-service.js"
/*!************************************************************!*\
  !*** ./front/assets/js/components/log-view/api-service.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LogViewerApiService: () => (/* binding */ LogViewerApiService)
/* harmony export */ });
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils.js */ "./front/assets/js/utils.js");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
/* global jQuery, ajaxurl, dbg_lv_backend_data, confirm, location, URL, XMLHttpRequest */

class LogViewerApiService {
  constructor(table) {
    this.table = table;
  }
  async fillInitialData() {
    try {
      const rawResponse = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_run_live_updates',
        initial: true,
        group_entries: this.isGroupingEnabled(),
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!rawResponse) return;
      const response = JSON.parse(rawResponse);
      if (response.data.length > 0) {
        this.table.rows.add(response.data).draw();
      }
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
    }
  }
  async updateLogs(onBeforeUpdate) {
    try {
      if (onBeforeUpdate) onBeforeUpdate();
      const rawResponse = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_run_live_updates',
        group_entries: this.isGroupingEnabled(),
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!rawResponse) return;
      const response = JSON.parse(rawResponse);
      if (response.action === 'clear') {
        this.table.clear().draw();
        return;
      }
      if (response.data.length > 0) {
        this._mergeGroupedEntries(response.data);
      }
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
    }
  }

  /**
   * Returns whether grouping is currently enabled.
   * Reads from dbg_lv_backend_data which is kept in sync by changeGroupEntries().
   */
  isGroupingEnabled() {
    return dbg_lv_backend_data.group_entries !== '0';
  }

  /**
   * Persist the group-entries preference via AJAX.
   */
  async changeGroupEntries(enabled) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_group_entries',
          value: enabled ? '1' : '0',
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      if (!response.success) {
        throw new Error(response.data);
      }

      // Keep backend data in sync so further requests use the right value
      dbg_lv_backend_data.group_entries = enabled ? '1' : '0';
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }

  /**
   * Merge incoming grouped entries into the existing table rows.
   * Entries that share a hash with an existing row have their count incremented
   * and their timestamp/datetime updated if the incoming entry is more recent.
   * Entries with no matching row are added as new rows.
   * When grouping is disabled, entries are simply appended.
   *
   * @param {Array} newGroups Grouped entries received from the server.
   */
  _mergeGroupedEntries(newGroups) {
    if (!this.isGroupingEnabled()) {
      this.table.rows.add(newGroups).draw();
      return;
    }
    const additions = [];
    newGroups.forEach(newGroup => {
      if (!newGroup.hash) {
        additions.push(newGroup);
        return;
      }
      let found = false;
      this.table.rows().every(function () {
        const rowData = this.data();
        if (rowData.hash === newGroup.hash) {
          const mergedCount = (rowData.count || 1) + (newGroup.count || 1);
          const useNew = newGroup.timestamp > rowData.timestamp;
          this.data(Object.assign({}, rowData, {
            count: mergedCount,
            timestamp: useNew ? newGroup.timestamp : rowData.timestamp,
            datetime: useNew ? newGroup.datetime : rowData.datetime
          }));
          found = true;
          return false; // break
        }
      });
      if (!found) {
        additions.push(newGroup);
      }
    });
    if (additions.length > 0) {
      this.table.rows.add(additions);
    }
    this.table.draw();
  }
  async toggleDebugMode(isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_debug_mode',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      const {
        data
      } = response;
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(`${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('debug_mode')} ${data.state}`, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async toggleDebugScripts(isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_debug_scripts',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      const {
        data
      } = response;
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(`${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('debug_scripts')} ${data.state}`, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async toggleLogInFile(isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_log_in_file',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      const {
        data
      } = response;
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(`${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('debug_log_scripts')} ${data.state}`, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async toggleDisplayErrors(isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_display_errors',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      const {
        data
      } = response;
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(`${(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('display_errors')} ${data.state}`, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async isDebugLogPubliclyAccessible() {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_is_debug_log_publicly_accessible',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      return response;
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async enableLogging() {
    const response = await jQuery.post(ajaxurl, {
      action: 'dbg_lv_first_run_enable_logging',
      wp_nonce: dbg_lv_backend_data.ajax_nonce
    });
    if (!response.success) {
      throw new Error(response.data);
    }
    return response;
  }
  async clearLog() {
    try {
      if (!confirm((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('flush_log_confirmation'))) {
        return null;
      }
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_log_viewer_clear_log',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)((0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.t)('log_was_cleared'), 'success');
      location.reload();
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  downloadLog() {
    jQuery.post({
      url: ajaxurl,
      data: {
        action: 'dbg_lv_log_viewer_download_log',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      },
      xhr: function () {
        const xhr = new XMLHttpRequest();
        xhr.responseType = 'blob';
        return xhr;
      },
      success: response => {
        const url = URL.createObjectURL(response);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'debug.log');
        document.body.append(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      },
      error: (xhr, status, error) => {
        (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      }
    });
  }
  async changeLogsUpdateMode(mode) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_logs_update_mode',
          mode,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
      return response;
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async changeDatetimeFormat(format) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_datetime_format',
          format,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async changeTimezone(timezone) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_timezone',
          timezone,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async sendTestAlertEmail(emails) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_send_test_alert_email',
          emails,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
  }
  async changeAutoRefreshInterval(interval) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_auto_refresh_interval',
          interval,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      });
      if (!response.success) {
        throw new Error(response.data);
      }
      dbg_lv_backend_data.log_updates_interval = interval;
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(response.data, 'success');
    } catch (error) {
      (0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.showToast)(error, 'error');
      throw error;
    }
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