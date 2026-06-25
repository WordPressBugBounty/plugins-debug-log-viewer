/* global jQuery, ajaxurl, dbg_lv_backend_data, confirm, location, URL, XMLHttpRequest */
import { showToast, t } from '../../utils.js'

export class LogViewerApiService {
  constructor (table) {
    this.table = table
  }

  async fillInitialData () {
    try {
      const rawResponse = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_run_live_updates',
        initial: true,
        group_entries: this.isGroupingEnabled(),
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      if (!rawResponse) return

      const response = JSON.parse(rawResponse)

      if (response.data.length > 0) {
        this.table.rows.add(response.data).draw()
      }
    } catch (error) {
      showToast(error, 'error')
    }
  }

  async updateLogs (onBeforeUpdate) {
    try {
      if (onBeforeUpdate) onBeforeUpdate()

      const rawResponse = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_run_live_updates',
        group_entries: this.isGroupingEnabled(),
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      if (!rawResponse) return

      const response = JSON.parse(rawResponse)

      if (response.action === 'clear') {
        this.table.clear().draw()
        return
      }

      if (response.data.length > 0) {
        this._mergeGroupedEntries(response.data)
      }
    } catch (error) {
      showToast(error, 'error')
    }
  }

  /**
   * Returns whether grouping is currently enabled.
   * Reads from dbg_lv_backend_data which is kept in sync by changeGroupEntries().
   */
  isGroupingEnabled () {
    return dbg_lv_backend_data.group_entries !== '0'
  }

  /**
   * Persist the group-entries preference via AJAX.
   */
  async changeGroupEntries (enabled) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_group_entries',
          value: enabled ? '1' : '0',
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }

      // Keep backend data in sync so further requests use the right value
      dbg_lv_backend_data.group_entries = enabled ? '1' : '0'
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  /**
   * Persist the scroll mode preference via AJAX (Pro only).
   * @param {'PAGINATION'|'INFINITE'} mode
   */
  async changeScrollMode (mode) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_scroll_mode',
          mode,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }

      dbg_lv_backend_data.scroll_mode = mode
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
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
  _mergeGroupedEntries (newGroups) {
    if (!this.isGroupingEnabled()) {
      this.table.rows.add(newGroups).draw()
      return
    }

    const additions = []

    newGroups.forEach((newGroup) => {
      if (!newGroup.hash) {
        additions.push(newGroup)
        return
      }

      let found = false

      this.table.rows().every(function () {
        const rowData = this.data()
        if (rowData.hash === newGroup.hash) {
          const mergedCount = (rowData.count || 1) + (newGroup.count || 1)
          const useNew = newGroup.timestamp > rowData.timestamp
          this.data(Object.assign({}, rowData, {
            count: mergedCount,
            timestamp: useNew ? newGroup.timestamp : rowData.timestamp,
            datetime: useNew ? newGroup.datetime : rowData.datetime
          }))
          found = true
          return false // break
        }
      })

      if (!found) {
        additions.push(newGroup)
      }
    })

    if (additions.length > 0) {
      this.table.rows.add(additions)
    }

    this.table.draw()
  }

  async toggleDebugMode (isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_debug_mode',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      const { data } = response

      if (!response.success) {
        throw new Error(response.data)
      }

      showToast(`${t('debug_mode')} ${data.state}`, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async toggleDebugScripts (isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_debug_scripts',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      const { data } = response

      if (!response.success) {
        throw new Error(response.data)
      }

      showToast(`${t('debug_scripts')} ${data.state}`, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async toggleLogInFile (isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_log_in_file',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })
      const { data } = response

      if (!response.success) {
        throw new Error(response.data)
      }

      showToast(`${t('debug_log_scripts')} ${data.state}`, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async toggleDisplayErrors (isChecked) {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_toggle_display_errors',
        state: +isChecked,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      const { data } = response

      if (!response.success) {
        throw new Error(response.data)
      }

      showToast(`${t('display_errors')} ${data.state}`, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async isDebugLogPubliclyAccessible () {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_is_debug_log_publicly_accessible',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      if (!response.success) {
        throw new Error(response.data)
      }

      return response
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async enableLogging () {
    const response = await jQuery.post(ajaxurl, {
      action: 'dbg_lv_first_run_enable_logging',
      wp_nonce: dbg_lv_backend_data.ajax_nonce
    })

    if (!response.success) {
      throw new Error(response.data)
    }

    return response
  }

  async clearLog () {
    try {
      if (!confirm(t('flush_log_confirmation'))) {
        return null
      }

      const response = await jQuery.post(ajaxurl, {
        action: 'dbg_lv_log_viewer_clear_log',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      if (!response.success) {
        throw new Error(response.data)
      }

      showToast(t('log_was_cleared'), 'success')
      location.reload()
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  downloadLog () {
    jQuery.post({
      url: ajaxurl,
      data: {
        action: 'dbg_lv_log_viewer_download_log',
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      },
      xhr: function () {
        const xhr = new XMLHttpRequest()
        xhr.responseType = 'blob'
        return xhr
      },
      success: (response) => {
        const url = URL.createObjectURL(response)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'debug.log')

        document.body.append(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
      },
      error: (xhr, status, error) => {
        showToast(error, 'error')
      }
    })
  }

  async changeLogsUpdateMode (mode) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_logs_update_mode',
          mode,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      showToast(response.data, 'success')
      return response
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async changeDatetimeFormat (format) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_datetime_format',
          format,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async changeTimezone (timezone) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_timezone',
          timezone,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async sendTestAlertEmail (emails) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_send_test_alert_email',
          emails,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

  async changeAutoRefreshInterval (interval) {
    try {
      const response = await jQuery.post({
        url: ajaxurl,
        data: {
          action: 'dbg_lv_change_auto_refresh_interval',
          interval,
          wp_nonce: dbg_lv_backend_data.ajax_nonce
        }
      })

      if (!response.success) {
        throw new Error(response.data)
      }

      dbg_lv_backend_data.log_updates_interval = interval
      showToast(response.data, 'success')
    } catch (error) {
      showToast(error, 'error')
      throw error
    }
  }

}

