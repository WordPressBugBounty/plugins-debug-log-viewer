/* global localStorage */
import { removePublicAccessWarning } from '../log-popover-renderer.js'

/**
 * Handles the alert dismissal functionality
 */
export class AlertHandler {
  constructor () {
    this.dismissKey = 'dbg_lv_public_log_alert_dismissed'
    this.dismissSelector = '#dbg_lv_dismiss_alert'
    this.weekInMilliseconds = 7 * 24 * 60 * 60 * 1000 // 7 days in milliseconds
  }

  /**
   * Initialize the alert handler
   */
  init () {
    // Add event delegation for dismiss buttons in dynamically created popovers
    $(document).off('click', this.dismissSelector).on('click', this.dismissSelector, function (e) {
      e.preventDefault()
      this.dismissForWeek()
      removePublicAccessWarning()
    }.bind(this))
  }

  /**
   * Dismiss the alert for a week
   */
  dismissForWeek () {
    // Store current timestamp in localStorage
    localStorage.setItem(this.dismissKey, new Date().getTime().toString())
  }

  /**
   * Check if the alert is currently dismissed
   * @return {boolean} True if the alert is currently dismissed
   */
  isCurrentlyDismissed () {
    const dismissedTimestamp = localStorage.getItem(this.dismissKey)

    if (dismissedTimestamp) {
      const currentTime = new Date().getTime()
      const dismissedTime = parseInt(dismissedTimestamp, 10)

      // If less than a week has passed, the alert is considered dismissed
      return currentTime - dismissedTime < this.weekInMilliseconds
    }

    return false
  }
}
