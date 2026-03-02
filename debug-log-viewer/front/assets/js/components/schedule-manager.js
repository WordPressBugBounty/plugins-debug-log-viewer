/* global jQuery, ajaxurl, dbg_lv_backend_data */
import { t } from '../utils.js'

/**
 * Generic Schedule Manager
 * Handles dynamic display and countdown for scheduled tasks
 */
export class ScheduleManager {
  constructor (containerId, ajaxAction) {
    this.container = jQuery(`#${containerId}`)
    this.timeElement = this.container.find('.next-time')
    this.countdownElement = this.container.find('.next-countdown')
    this.ajaxAction = ajaxAction
    this.updateInterval = null
    this.targetTimestamp = null
  }

  async updateScheduleDisplay () {
    try {
      const response = await jQuery.post(ajaxurl, {
        action: this.ajaxAction,
        wp_nonce: dbg_lv_backend_data.ajax_nonce
      })

      if (!response.success) {
        throw new Error(response.data || t('request_error'))
      }

      if (response.scheduled) {
        this.targetTimestamp = response.timestamp
        this.timeElement.text(response.formatted_time)
        this.countdownElement.text(t('in') + ' ' + response.time_until)
        this.container.show()

        // Start countdown timer
        this.startCountdownTimer()
      } else {
        this.hide()
      }
    } catch (error) {
      console.error('Failed to update schedule display:', error)
      this.hide()
    }
  }

  startCountdownTimer () {
    this.stopCountdownTimer()

    this.updateInterval = setInterval(() => {
      if (!this.targetTimestamp) {
        return
      }

      const now = Math.floor(Date.now() / 1000)
      const timeDiff = this.targetTimestamp - now

      if (timeDiff <= 0) {
        this.stopCountdownTimer()
        // Refresh to get new schedule
        this.updateScheduleDisplay()
        return
      }

      const timeUntil = this.formatTimeRemaining(timeDiff)
      this.countdownElement.text(t('in') + ' ' + timeUntil)
    }, 60000) // Update every minute
  }

  stopCountdownTimer () {
    if (this.updateInterval) {
      clearInterval(this.updateInterval)
      this.updateInterval = null
    }
  }

  formatTimeRemaining (seconds) {
    const days = Math.floor(seconds / 86400)
    const hours = Math.floor((seconds % 86400) / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    if (days > 0) {
      if (hours > 0) {
        return `${days} ${days === 1 ? t('day') : t('days')} ${hours} ${hours === 1 ? t('hour') : t('hours')}`
      } else {
        return `${days} ${days === 1 ? t('day') : t('days')}`
      }
    } else if (hours > 0) {
      return `${hours} ${hours === 1 ? t('hour') : t('hours')}`
    } else {
      return `${minutes} ${minutes === 1 ? t('minute') : t('minutes')}`
    }
  }

  hide () {
    this.container.hide()
    this.stopCountdownTimer()
  }

  show () {
    this.updateScheduleDisplay()
  }

  destroy () {
    this.stopCountdownTimer()
  }
}
