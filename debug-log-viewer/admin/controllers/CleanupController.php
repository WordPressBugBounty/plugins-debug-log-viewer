<?php
/**
 * Cleanup Controller
 * 
 * Manages the WordPress Cron scheduling and execution of automatic log cleanup
 * 
 * @package DebugLogViewer
 * @since 1.0.0
 */

namespace DebugLogViewer\Admin\Controllers;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Services\LogCleanupService;
use DebugLogViewer\Admin\Services\EmailService;
use DebugLogViewer\Admin\Traits\ScheduleTrait;

require_once realpath(__DIR__) . '/BaseController.php';
require_once realpath(__DIR__) . '/../services/LogCleanup.php';
require_once realpath(__DIR__) . '/../services/Email.php';
require_once realpath(__DIR__) . '/../traits/ScheduleTrait.php';

class CleanupController extends BaseController
{

	use ScheduleTrait;

	const CRON_HOOK = 'dbg_lv_auto_cleanup_log';
	const SCHEDULE_NAME = 'DBG_LV_AUTO_CLEANUP';

	private $cleanupService;

	public function __construct()
	{
		$this->cleanupService = new LogCleanupService();
	}

	public function init()
	{
		$this->register_hooks();
	}

	private function register_hooks()
	{
		add_action(self::CRON_HOOK, [$this, 'executeScheduledCleanup']);
		add_action('admin_init', [$this, 'handleManualCleanup']);
		add_action('admin_notices', [$this, 'displayCleanupNotices']);
		add_action('wp_ajax_dbg_lv_run_manual_cleanup', [$this, 'ajaxRunManualCleanup']);
		add_action('wp_ajax_dbg_lv_get_cleanup_stats', [$this, 'ajaxGetCleanupStats']);
	}

	public function schedule_cleanup($recurrence = 'daily')
	{
		// Free users can only use daily schedule
		if (!dbg_lv()->is_premium() && $recurrence !== 'daily') {
			$recurrence = 'daily';
		}

		if (wp_next_scheduled(self::CRON_HOOK)) {
			$this->unschedule_cleanup();
		}

		// Calculate next run time based on schedule interval
		$time = new \DateTime();
		$time->add($this->getScheduleInterval($recurrence));
		$next_run = $time->getTimestamp();

		$scheduled = wp_schedule_event($next_run, $recurrence, self::CRON_HOOK);

		if (false === $scheduled) {
			error_log('Debug Log Viewer: Failed to schedule auto-cleanup cron job');
			return false;
		}

		update_option(LogCleanupService::OPTION_CLEANUP_SCHEDULE, $recurrence);
		update_option(LogCleanupService::OPTION_CLEANUP_NEXT_RUN, wp_next_scheduled(self::CRON_HOOK));

		return true;
	}

	public function unschedule_cleanup()
	{
		$timestamp = wp_next_scheduled(self::CRON_HOOK);

		if ($timestamp) {
			wp_unschedule_event($timestamp, self::CRON_HOOK);
			delete_option(LogCleanupService::OPTION_CLEANUP_SCHEDULE);
			delete_option(LogCleanupService::OPTION_CLEANUP_NEXT_RUN);
			return true;
		}

		return false;
	}

	public function executeScheduledCleanup()
	{
		if (! get_option(LogCleanupService::OPTION_CLEANUP_ENABLED, false)) {
			return;
		}

		$result = $this->cleanupService->executeCleanup();

		$this->log_cleanup_result($result);

		update_option(LogCleanupService::OPTION_CLEANUP_NEXT_RUN, wp_next_scheduled(self::CRON_HOOK));

		if ($result['success'] && isset($result['old_size']) && $result['old_size'] > 0) {
			$this->maybe_send_cleanup_notification($result);
		}

		return $result;
	}

	public function handleManualCleanup()
	{
		if (! isset($_GET['dbg_lv_manual_cleanup']) || ! isset($_GET['_wpnonce'])) {
			return;
		}

		if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dbg_lv_manual_cleanup')) {
			wp_die(esc_html__('Security check failed', 'debug-log-viewer'));
		}

		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to perform this action', 'debug-log-viewer'));
		}

		$result = $this->cleanupService->executeCleanup();

		set_transient('dlv_cleanup_result', $result, 60);

		$redirect_url = remove_query_arg(array('dbg_lv_manual_cleanup', '_wpnonce'));
		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * AJAX handler for manual cleanup
	 */
	public function ajaxRunManualCleanup()
	{
		$this->verifyAjaxRequest();

		$result = $this->cleanupService->executeCleanup();

		if ($result['success']) {
			wp_send_json_success(array(
				'message' => $result['message'],
				'result'  => $result,
			));
		} else {
			wp_send_json_error(array(
				'message' => $result['message'],
				'result'  => $result,
			));
		}
	}

	/**
	 * AJAX handler to get fresh cleanup stats and history
	 */
	public function ajaxGetCleanupStats()
	{
		$this->verifyAjaxRequest();

		$stats   = $this->cleanupService->get_cleanup_stats();
		$history = $this->getCleanupHistory(10);
		// Format history for frontend
		$formatted_history = array();
		foreach ($history as $entry) {
			$method_labels = array(
				'truncateKeepLast'  => __('Truncate', 'debug-log-viewer'),
				'clear'               => __('Clear', 'debug-log-viewer'),
				'archiveAndRotate'  => __('Archive', 'debug-log-viewer'),
				'unknown'             => __('Unknown', 'debug-log-viewer'),
			);

			$reduction_html = '—';
			if (isset($entry['old_size']) && isset($entry['new_size']) && $entry['old_size'] > 0) {
				$reduction         = $entry['old_size'] - $entry['new_size'];
				$reduction_percent = round(($reduction / $entry['old_size']) * 100, 1);
				$reduction_html    = '<span class="text-success">' . size_format($reduction, 2) . ' (' . $reduction_percent . '%)</span>';
			}

			$formatted_history[] = array(
				'date'        => isset($entry['timestamp']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['timestamp'])) : '-',
				'success'     => isset($entry['success']) ? $entry['success'] : false,
				'method'      => isset($entry['method']) ? esc_html($method_labels[$entry['method']] ?? $entry['method']) : '-',
				'size_before' => isset($entry['old_size']) && $entry['old_size'] > 0 ? size_format($entry['old_size'], 2) : '—',
				'size_after'  => isset($entry['new_size']) && $entry['new_size'] > 0 ? size_format($entry['new_size'], 2) : '—',
				'reduction'   => $reduction_html,
				'message'     => isset($entry['message']) ? esc_html($entry['message']) : '',
			);
		}

		wp_send_json_success(array(
			'stats'   => $stats,
			'history' => $formatted_history,
		));
	}

	public function displayCleanupNotices()
	{
		$result = get_transient('dlv_cleanup_result');

		if (! $result) {
			return;
		}

		delete_transient('dlv_cleanup_result');

		$class = $result['success'] ? 'notice-success' : 'notice-error';
		$message = esc_html($result['message']);

		if (isset($result['old_size']) && isset($result['new_size'])) {
			$old_size_mb = round($result['old_size'] / 1024 / 1024, 2);
			$new_size_mb = round($result['new_size'] / 1024 / 1024, 2);
			$message .= sprintf(
				' (Reduced from %s MB to %s MB)',
				number_format($old_size_mb, 2),
				number_format($new_size_mb, 2)
			);
		}

		printf(
			'<div class="notice %s is-dismissible"><p><strong>Debug Log Viewer:</strong> %s</p></div>',
			esc_attr($class),
			wp_kses_post($message)
		);
	}

	private function log_cleanup_result($result)
	{
		// Only log history for Pro users (cleanup history is a Pro feature)
		if (! dbg_lv()->is_premium()) {
			return;
		}

		$history = get_option(LogCleanupService::OPTION_CLEANUP_HISTORY, []);

		$history[] = array(
			'timestamp' => current_time('mysql'),
			'success'   => $result['success'],
			'message'   => $result['message'],
			'old_size'  => $result['old_size'] ?? 0,
			'new_size'  => $result['new_size'] ?? 0,
			'method'    => $result['method'] ?? 'unknown',
		);

		if (count($history) > 50) {
			$history = array_slice($history, -50);
		}

		update_option(LogCleanupService::OPTION_CLEANUP_HISTORY, $history);

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$log_message = sprintf(
				'Debug Log Viewer Auto-Cleanup: %s (Method: %s)',
				$result['message'],
				$result['method'] ?? 'N/A'
			);
			error_log($log_message);
		}
	}

	private function maybe_send_cleanup_notification($result)
	{
		if (! get_option(LogCleanupService::OPTION_CLEANUP_NOTIFICATIONS, false)) {
			return;
		}

		$admin_email = get_option('admin_email');
		$site_name   = get_option('blogname');
		$old_size_mb = round($result['old_size'] / 1024 / 1024, 2);
		$new_size_mb = round($result['new_size'] / 1024 / 1024, 2);

		$email_service = new EmailService();
		$email_service->setEmails(array($admin_email));
		$email_service->setSubject(
			sprintf('[%s] Debug Log Auto-Cleanup Performed', $site_name)
		);
		$email_service->setBody(
			sprintf(
				"<p>Debug Log Auto-Cleanup was performed on <strong>%s</strong></p>" .
					"<h3>Details:</h3>" .
					"<ul>" .
					"<li><strong>Previous size:</strong> %s MB</li>" .
					"<li><strong>New size:</strong> %s MB</li>" .
					"<li><strong>Method:</strong> %s</li>" .
					"<li><strong>Time:</strong> %s</li>" .
					"</ul>" .
					"<p><em>This is an automated notification from Debug Log Viewer plugin.</em></p>",
				esc_html($site_name),
				number_format($old_size_mb, 2),
				number_format($new_size_mb, 2),
				esc_html($result['method'] ?? 'N/A'),
				current_time('mysql')
			)
		);
		$email_service->send();
	}

	/**
	 * Get cleanup schedule information
	 *
	 * @return array Schedule information
	 */
	public function get_schedule_info()
	{
		$next_run = wp_next_scheduled(self::CRON_HOOK);

		return array(
			'is_scheduled'    => (bool) $next_run,
			'next_run'        => $next_run,
			'next_run_human'  => $next_run ? human_time_diff(time(), $next_run) : '',
			'schedule'        => get_option(LogCleanupService::OPTION_CLEANUP_SCHEDULE, 'daily'),
		);
	}

	/**
	 * Get formatted next scheduled time (absolute date/time)
	 *
	 * @return string|null Formatted time string or null if not scheduled
	 */
	public function getFormattedNextScheduledTime()
	{
		$timestamp = wp_next_scheduled(self::CRON_HOOK);

		if (! $timestamp) {
			return null;
		}

		$datetime    = new \DateTime('@' . $timestamp);
		$datetime->setTimezone(wp_timezone());
		$date_format = get_option('date_format', 'F j, Y');
		$time_format = get_option('time_format', 'g:i a');

		return sprintf(
			/* translators: %1$s: date, %2$s: time */
			__('Next run: %1$s at %2$s', 'debug-log-viewer'),
			$datetime->format($date_format),
			$datetime->format($time_format)
		);
	}

	/**
	 * Get time until next cleanup (relative time)
	 *
	 * @return string|null Relative time string or null if not scheduled
	 */
	public function getTimeUntilNextCleanup()
	{
		$timestamp = wp_next_scheduled(self::CRON_HOOK);

		if (!$timestamp) {
			return null;
		}

		$current_time = time();
		$time_diff    = $timestamp - $current_time;

		if ($time_diff <= 0) {
			return __('Running soon...', 'debug-log-viewer');
		}

		$days    = floor($time_diff / 86400);
		$hours   = floor(($time_diff % 86400) / 3600);
		$minutes = floor(($time_diff % 3600) / 60);

		if ($days > 0) {
			if ($hours > 0) {
				return sprintf(
					/* translators: %d: number of days */
					_n('%d day', '%d days', $days, 'debug-log-viewer') . ' ' .
						/* translators: %d: number of hours */
						_n('%d hour', '%d hours', $hours, 'debug-log-viewer'),
					$days,
					$hours
				);
			} else {
				return sprintf(
					/* translators: %d: number of days */
					_n('%d day', '%d days', $days, 'debug-log-viewer'),
					$days
				);
			}
		} elseif ($hours > 0) {
			return sprintf(
				/* translators: %d: number of hours */
				_n('%d hour', '%d hours', $hours, 'debug-log-viewer'),
				$hours
			);
		} else {
			return sprintf(
				/* translators: %d: number of minutes */
				_n('%d minute', '%d minutes', $minutes, 'debug-log-viewer'),
				$minutes
			);
		}
	}

	/**
	 * Get next scheduled time for AJAX response
	 *
	 * @return array AJAX response data
	 */
	public function getNextScheduleTimeForAjax()
	{
		$timestamp = wp_next_scheduled(self::CRON_HOOK);

		if (! $timestamp) {
			return [
				'success'   => true,
				'scheduled' => false,
				'message'   => __('Not scheduled', 'debug-log-viewer'),
			];
		}

		return [
			'success'        => true,
			'scheduled'      => true,
			'timestamp'      => $timestamp,
			'formatted_time' => $this->getFormattedNextScheduledTime(),
			'time_until'     => $this->getTimeUntilNextCleanup(),
		];
	}

	/**
	 * Get cleanup history
	 *
	 * @param int $limit Number of entries to retrieve
	 * @return array Cleanup history
	 */
	public function getCleanupHistory($limit = 10)
	{
		// Cleanup history is a Pro-only feature
		if (! dbg_lv()->is_premium()) {
			return [];
		}

		$history = get_option(LogCleanupService::OPTION_CLEANUP_HISTORY, []);

		if ($limit > 0) {
			$history = array_slice($history, -$limit);
		}

		return array_reverse($history); // Most recent first
	}

	/**
	 * Initialize cleanup on plugin activation
	 * 
	 * Should be called during plugin activation
	 */
	public static function activate()
	{
		// Set default options
		add_option(LogCleanupService::OPTION_MAX_LOG_SIZE, 50); // 50 MB default
		add_option(LogCleanupService::OPTION_CLEANUP_ENABLED, false); // Disabled by default
		add_option(LogCleanupService::OPTION_CLEANUP_METHOD, LogCleanupService::METHOD_TRUNCATE_KEEP_LAST);
		add_option(LogCleanupService::OPTION_RETENTION_PERCENT, 10); // Keep last 10%
		add_option(LogCleanupService::OPTION_CLEANUP_SCHEDULE, 'daily');
		add_option(LogCleanupService::OPTION_CLEANUP_NOTIFICATIONS, false);
	}

	/**
	 * Cleanup on plugin deactivation
	 * 
	 * Should be called during plugin deactivation
	 */
	public static function deactivate()
	{
		// Unschedule cron job
		$timestamp = wp_next_scheduled(self::CRON_HOOK);
		if ($timestamp) {
			wp_unschedule_event($timestamp, self::CRON_HOOK);
		}
	}

	/**
	 * Save cleanup settings
	 */
	public function saveSettings()
	{
		$this->verifyAjaxRequest('dbg_lv_save_cleanup_settings');


		// Collect settings - using camelCase keys to match validate_settings() expectations
		$settings = [
			'cleanupEnabled'       => isset($_POST['cleanup_enabled']) && $_POST['cleanup_enabled'] == '1',
			'maxLogSize'           => isset($_POST['max_log_size']) ? absint($_POST['max_log_size']) : 50,
			'cleanupMethod'        => isset($_POST['cleanup_method']) ? sanitize_text_field(wp_unslash($_POST['cleanup_method'])) : 'keep_last',
			'retentionPercentage'  => isset($_POST['retention_percentage']) ? absint($_POST['retention_percentage']) : 10,
			'emailNotifications'   => isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1',
		];
		// Validate settings using the service
		$validated = LogCleanupService::validate_settings($settings);

		// Save to database - using keys returned from validate_settings()
		update_option(LogCleanupService::OPTION_CLEANUP_ENABLED, $validated['cleanupEnabled']);
		update_option(LogCleanupService::OPTION_MAX_LOG_SIZE, $validated['maxLogSize']);
		update_option(LogCleanupService::OPTION_CLEANUP_METHOD, $validated['cleanupMethod']);
		update_option(LogCleanupService::OPTION_CLEANUP_NOTIFICATIONS, $validated['emailNotifications'] ?? false);
		update_option(LogCleanupService::OPTION_RETENTION_PERCENT, $validated['retentionPercentage']);

		// Handle cron scheduling
		if ($validated['cleanupEnabled']) {
			$schedule = isset($_POST['cleanup_schedule']) ? sanitize_text_field(wp_unslash($_POST['cleanup_schedule'])) : 'daily';
			$this->schedule_cleanup($schedule);
			$message = __('Auto-Cleanup enabled successfully', 'debug-log-viewer');
		} else {
			$this->unschedule_cleanup();
			$message = __('Auto-Cleanup disabled successfully', 'debug-log-viewer');
		}

		wp_send_json_success([
			'message'  => $message,
			'settings' => $validated,
		]);
	}
}
