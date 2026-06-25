<?php

namespace DebugLogViewer\Admin\Controllers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Traits\ScheduleTrait;
use DebugLogViewer\Admin\Models\LogModel;
use DebugLogViewer\Admin\Helpers\Utils;
use DebugLogViewer\Admin\Services\LogCleanupService;
use DebugLogViewer\Admin\Controllers\FreemiusController;

require_once realpath(__DIR__) . '/../../vendor/autoload.php';
require_once realpath(__DIR__) . '/BaseController.php';
require_once realpath(__DIR__) . '/LiveUpdatesController.php';
require_once realpath(__DIR__) . '/../../admin/models/LogModel.php';
require_once realpath(__DIR__) . '/../../front/views/pages/index.tpl.php';
require_once realpath(__DIR__) . '/../../admin/helpers/utils.php';
require_once realpath(__DIR__) . '/../../admin/traits/ScheduleTrait.php';
require_once realpath(__DIR__) . '/../../admin/services/Email.php';

class LogController extends BaseController {

	use ScheduleTrait;

	const SCHEDULE_MAIL_SEND = 'DBG_LV_NOTIFY_LOG_CONTROLLER';

	private $configEditor;
	private $logModel;
	private $notificationController;

	public function __construct( ?LogModel $logModel = null, ?AlertController $notificationController = null ) {

		$this->logModel               = $logModel ?? new LogModel();
		$this->notificationController = $notificationController ?? new AlertController();
		try {
			$this->configEditor = new \WPConfigTransformer($this->logModel->getWpConfigPath());
		} catch (\Exception $error) {
			// Please, make sure permissions and path is correct.
			// The plugin need an access to wp-config.php to manage debugging constants
		}
	}

	public static function isCustomLoggingPath() {
		return LogModel::isCustomLoggingPath();
	}

	public function firstRunEnableLogging() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		try {

			$path = $this->logModel->getLogFilePath();
			$this->configEditor->update('constant', 'WP_DEBUG', '1');

			if (!self::isCustomLoggingPath()) {
				$this->configEditor->update('constant', 'WP_DEBUG_LOG', '1');
			}

			if (filesize($path) == 0) {
				$message     = __('This is a demo entry. Debugging is enabled. Any notices, warnings, or errors that occur on your site will appear here.', 'debug-log-viewer');
				$demo_string = '[' . gmdate('d-M-Y H:i:s T') . '] PHP Notice: <b>' . $message . '</b>  in ' . Utils::getDocumentRoot() . "/example.php on line 0\n";
				file_put_contents($path, $demo_string);
			}

			wp_send_json_success();
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function toggleDebugMode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$state = $this->prepareState();

		try {
			$this->configEditor->update('constant', 'WP_DEBUG', $state);

			wp_send_json_success([
				'state' => $this->stateToText($state),
			]);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function toggleDebugScripts() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$state = $this->prepareState();

		try {
			$this->configEditor->update('constant', 'SCRIPT_DEBUG', $state);

			wp_send_json_success([
				'state' => $this->stateToText($state),
			]);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function toggleLogInFile() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$state = $this->prepareState();

		try {
			if ($state == '1') {
				$this->configEditor->update('constant', 'WP_DEBUG', $state);
			}

			$this->configEditor->update('constant', 'WP_DEBUG_LOG', $state);

			wp_send_json_success([
				'state' => $this->stateToText($state),
			]);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function toggleDisplayErrors() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$state = $this->prepareState();

		try {
			if ($state == '1') {
				$this->configEditor->update('constant', 'WP_DEBUG', $state);
			}

			$this->configEditor->update('constant', 'WP_DEBUG_DISPLAY', $state);

			wp_send_json_success([
				'state' => $this->stateToText($state),
			]);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function clear() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		try {
			$path = $this->logModel->getLogFilePath();

			if (is_file($path) && file_exists($path)) {

				if (is_writable($path)) {
					file_put_contents($path, '');

					update_option(LogModel::LAST_POSITION_OPTION_NAME, 0); // reset the stored position

					wp_send_json_success();
				}

				throw new \Exception(__('The log file was found but cannot be cleared due to missing write permissions', 'debug-log-viewer'));
			}
			throw new \Exception(__('The log file was not found and cannot be removed', 'debug-log-viewer'));
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function download() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		try {
			$path = $this->logModel->getLogFilePath();

			if (is_file($path) && file_exists($path)) {

				$basename = basename($path);
				$filesize = filesize($path);

				header('Content-Description: File Transfer');
				header('Content-Type: text/plain');
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: 0');
				header("Content-Disposition: attachment; filename=$basename");
				header("Content-Length: $filesize");
				header('Pragma: public');

				flush();

				readfile($path);
				wp_die();
			}

			throw new \Exception(__('The log file was not found and cannot be removed', 'debug-log-viewer'));
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function getCurrentUserEmail() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		try {
			global $current_user;

			wp_send_json_success($current_user->user_email);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function prepareState() {
		if (!isset($_POST['state'])) {
			throw new \Exception('Empty state passed');
		}

		$state = sanitize_text_field(wp_unslash($_POST['state']));
		switch ( (int) $state) {
			case 0:
			case 1:
				return (string) $state;
			default:
				throw new \Exception(esc_html__('An incorrect state value was passed', 'debug-log-viewer'));
		}
	}

	public function getEntriesByLevel( $recurrence ) {
		$rows = $this->logModel->getParsedContent();

		if (!$rows) {
			return;
		}

		$errors = [];
		foreach ($this->notificationController->getLevelsToReport() as $level) {
			$errors[ $level ] = [];
		}

		$new_lines = 0;
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$datetime       = LogModel::getDatetime($row);
			$datetimeOffset = new \DateTime();
			$datetimeOffset->sub($this->getScheduleInterval($recurrence));
			$datetimeError = new \DateTime();
			$strToTime      = strtotime($datetime);
			$datetimeError->setTimestamp($strToTime);

			if ($datetimeError < $datetimeOffset) {
				continue;
			}

			$type = LogModel::getType($row);

			if (!in_array($type, $this->notificationController->getLevelsToReport())) {
				continue;
			}

			$line = LogModel::getLine($row);
			$file = LogModel::getFile($row);
			$text = LogModel::getDescription($row);
			$hash = LogModel::buildEntryHash($type, $text, $file, $line);

			if (array_key_exists($hash, $errors[ $type ])) {
				$errors[ $type ][ $hash ]['hits'] += 1;
			} else {
				$errors[ $type ][ $hash ] = [
					'timestamp'   => $strToTime * 1000,
					'datetime'    => $datetime,
					'line'        => $line,
					'file'        => $file,
					'type'        => $type,
					'description' => [
						'text'        => $text,
						'stack_trace' => LogModel::getStackTrace($row),
					],
					'hits'        => 1,
				];
			}
			$new_lines += 1;
		}

		return $new_lines ? $errors : null;
	}

	public function changeLogsUpdateMode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : null;

		$allowed_modes = [ 'AUTO', 'MANUAL' ];
		if (!in_array($mode, $allowed_modes, true)) {
			wp_send_json_error(__('Invalid mode value', 'debug-log-viewer'), 400);
		}

		// Retrieve the current mode setting
		$current_mode = get_option(LogModel::LOG_UPDATES_MODE_OPTION_NAME);
		if ($current_mode === $mode) {
			// If the current value matches the new value, consider it a success
			wp_send_json_success(__('Update mode saved', 'debug-log-viewer'));
		}

		// Attempt to update the mode setting
		if (update_option(LogModel::LOG_UPDATES_MODE_OPTION_NAME, $mode)) {
			wp_send_json_success(__('Update mode saved', 'debug-log-viewer'));
		} else {
			wp_send_json_error(__('Failed to save update mode', 'debug-log-viewer'), 500);
		}
	}

	public function changeDatetimeFormat() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$format = isset($_POST['format']) ? sanitize_text_field(wp_unslash($_POST['format'])) : null;

		if (!$format) {
			wp_send_json_error(__('Missing parameter: format', 'debug-log-viewer'), 400);
		}

		$allowed_formats = [ 'ABSOLUTE', 'RELATIVE' ];
		if (!in_array($format, $allowed_formats, true)) {
			wp_send_json_error(__('Invalid format value', 'debug-log-viewer'), 400);
		}

		// Retrieve the current format setting
		$current_format = get_option(LogModel::DATETIME_FORMAT_OPTION_NAME);
		if ($current_format === $format) {
			// If the current value matches the new value, consider it a success
			wp_send_json_success(__('Datetime format saved', 'debug-log-viewer'));
		}

		// Attempt to update the format setting
		if (update_option(LogModel::DATETIME_FORMAT_OPTION_NAME, $format)) {
			wp_send_json_success(__('Datetime format saved', 'debug-log-viewer'));
		} else {
			wp_send_json_error(__('Failed to save datetime format', 'debug-log-viewer'), 500);
		}
	}

	/**
	 * Handle timezone change request.
	 *
	 * @return void
	 */
	public function changeTimezone() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$timezone = isset($_POST['timezone']) ? sanitize_text_field(wp_unslash($_POST['timezone'])) : null;

		if ($timezone === null || (!empty($timezone) && false === LogModel::getDateTimeZone($timezone))) {
			wp_send_json_error(__('Invalid timezone', 'debug-log-viewer'), 400);
		}

		update_user_meta(get_current_user_id(), LogModel::TIMEZONE_OPTION_NAME, $timezone);
		wp_send_json_success(__('Timezone saved', 'debug-log-viewer'));
	}

	public function changeGroupEntries() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$value = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : null;

		if (!in_array($value, [ '0', '1' ], true)) {
			wp_send_json_error(__('Invalid value', 'debug-log-viewer'), 400);
		}

		update_user_meta(get_current_user_id(), LogModel::GROUP_ENTRIES_OPTION_NAME, $value);
		wp_send_json_success(__('Grouping saved', 'debug-log-viewer'));
	}

	public function sendTestAlertEmail() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$emails = isset($_POST['emails']) ? array_map('sanitize_email', wp_unslash($_POST['emails'])) : [];
		$emails = $this->limitNotificationEmails(array_filter($emails, 'is_email'));

		if (empty($emails)) {
			wp_send_json_error(__('At least one valid email address is required', 'debug-log-viewer'));
		}

		try {
			$this->notificationController->sendTestEmailToRecipients([
				'dbg_lv_notifications_emails' => $emails,
				'dbg_lv_notifications_levels' => $this->notificationController->getLevelsToReport(),
				'action'                      => $this->notificationController->action,
			]);
			wp_send_json_success(__('Test email sent', 'debug-log-viewer'));
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	public function changeAutoRefreshInterval() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		$interval = isset($_POST['interval']) ? (int) sanitize_text_field(wp_unslash($_POST['interval'])) : null;
		$allowed  = [ 5, 10, 15, 30, 60 ];

		if ($interval === null || !in_array($interval, $allowed, true)) {
			wp_send_json_error(__('Invalid interval value', 'debug-log-viewer'), 400);
		}

		update_option(LogModel::AUTO_REFRESH_INTERVAL_OPTION, $interval);
		wp_send_json_success(__('Refresh interval saved', 'debug-log-viewer'));
	}

	public function changeScrollMode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		if (!FreemiusController::is_premium()) {
			wp_send_json_error(__('This feature requires a Pro plan', 'debug-log-viewer'), 403);
		}

		$mode    = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : null;
		$allowed = [ LogModel::SCROLL_MODE_PAGINATION, LogModel::SCROLL_MODE_INFINITE ];

		if (!in_array($mode, $allowed, true)) {
			wp_send_json_error(__('Invalid scroll mode value', 'debug-log-viewer'), 400);
		}

		$current = get_option(LogModel::SCROLL_MODE_OPTION_NAME);
		if ($current === $mode) {
			wp_send_json_success(__('Scroll mode saved', 'debug-log-viewer'));
		}

		if (update_option(LogModel::SCROLL_MODE_OPTION_NAME, $mode)) {
			wp_send_json_success(__('Scroll mode saved', 'debug-log-viewer'));
		} else {
			wp_send_json_error(__('Failed to save scroll mode', 'debug-log-viewer'), 500);
		}
	}

	public function isDebugLogPubliclyAccessible() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		try {
			$path = $this->logModel->getLogFilePath();

		// If file doesn't exist, it's not accessible
		if (!file_exists($path)) {
			wp_send_json_success(['is_public' => false]);
		}
			// Convert file path to URL
			$site_url  = site_url();
			$site_path = wp_normalize_path(ABSPATH);
			$log_path  = wp_normalize_path($path);

			// Only proceed if the log is within the WordPress directory structure
			if (strpos($log_path, $site_path) === 0) {
				// Get the relative path and convert to URL
				$relative_path = str_replace($site_path, '', $log_path);
				$log_url       = trailingslashit($site_url) . ltrim($relative_path, '/');

				// Use HEAD request instead of GET (much more efficient)
				$response = wp_remote_head($log_url, [
					'timeout'     => 3, // Short timeout is sufficient for HEAD request
					'sslverify'   => false,
					'redirection' => 0,
				]);

				if (!is_wp_error($response)) {
					$is_public = wp_remote_retrieve_response_code($response) === 200;
					// If the response is 200, the file is publicly accessible
					if ($is_public) {
						wp_send_json_success([
							'is_public' => true,
							'info' => [
								'icon' => 'fa-globe',
								'type' => 'danger',
								'title' => __('Public Access', 'debug-log-viewer'),
								'message' => __('Log file is publicly accessible via URL', 'debug-log-viewer'),
								'key' => 'dbg_lv_public_log_alert_dismissed',
							]
						]);
					} else {
						// If the response is not 200, the file is not publicly accessible
						wp_send_json_success(['is_public' => false]);
					}
				}
			}

			wp_send_json_success(['is_public' => false]);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		}
	}

	private function stateToText( $state ): string {
		return (int) $state ? __('ON', 'debug-log-viewer') : __('OFF', 'debug-log-viewer');
	}

	public function getLogFileWarnings() {
		$path = $this->logModel->getLogFilePath();

		// Get file information and check for warnings
		$file_exists = file_exists($path);
		if(!$file_exists) {
			return [];
		}

		$warnings = [];

		// Check file size
		$file_size = filesize($path);
		if ($file_size > LogModel::LOG_FILE_LIMIT) {
			$limit = $this->logModel->getLogLimit(LogModel::UNIT_MEGABYTES, true);

			$warnings[] = [
				'icon' => 'fa-database',
				'type' => 'warning',
				'title' => __('Large File Size', 'debug-log-viewer'),
				/* translators: %1$s: file size limit, %2$s: amount of content displayed */
				'message' => sprintf(__('The file exceeds the %1$s limit, only the last %2$s will be displayed.', 'debug-log-viewer'), $limit, $limit)
			];
		}

		return $warnings;
	}

	public function getWarningsSeverity() {
		$warnings = $this->getLogFileWarnings();

		if (empty($warnings)) {
			return 'none';
		}

		// Check for danger level warnings (public access)
		foreach ($warnings as $warning) {
			if ($warning['type'] === 'danger') {
				return 'danger';
			}
		}

		// Default level is warning
		return 'warning';
	}

	/**
	 * Get popover data for JavaScript rendering
	 * @return array Data for wp_localize_script
	 */
	public function getPopoverData() {
		$path = $this->logModel->getLogFilePath();
		$file_exists = file_exists($path);

		$fileInfo = [];

		if ($file_exists) {
			$last_modified = filemtime($path);
			$file_size = $this->logModel->getLogSize(['with_measure_units' => true]);
			$permissions = substr(sprintf('%o', fileperms($path)), -4);
			$is_readable = is_readable($path);
			$is_writable = is_writable($path);

			$fileInfo = [
				'fileSize' => $file_size,
				'lastModified' => $last_modified ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $last_modified) : __('Unknown', 'debug-log-viewer'),
				'permissions' => $permissions . ' (' . ($is_readable ? 'R' : '-') . ($is_writable ? 'W' : '-') . ')',
			];
		}

		$warnings = $this->getLogFileWarnings();

		return [
			'fileInfo' => $fileInfo,
			'warnings' => $warnings
		];
	}

	/**
	 * Save cleanup settings
	 */
	public function saveCleanupSettings() {
		// Verify nonce
		$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'dbg_lv_save_cleanup_settings')) {
			wp_send_json_error(array('message' => __('Security check failed', 'debug-log-viewer')), 403);
		}

		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'debug-log-viewer')), 403);
		}

		// Collect settings
		$settings = array(
			'cleanup_enabled'      => isset($_POST['cleanup_enabled']) && $_POST['cleanup_enabled'] == '1',
			'max_log_size'         => isset($_POST['max_log_size']) ? absint($_POST['max_log_size']) : 50,
			'cleanup_method'       => isset($_POST['cleanup_method']) ? sanitize_text_field(wp_unslash($_POST['cleanup_method'])) : 'keep_last',
			'retention_percentage' => isset($_POST['retention_percentage']) ? absint($_POST['retention_percentage']) : 10,
			'email_notifications'  => isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1',
		);

		$validated = LogCleanupService::validate_settings($settings);

		// Save to database
		update_option(LogCleanupService::OPTION_CLEANUP_ENABLED, $validated['cleanup_enabled']);
		update_option(LogCleanupService::OPTION_MAX_LOG_SIZE, $validated['max_log_size']);
		update_option(LogCleanupService::OPTION_CLEANUP_METHOD, $validated['cleanup_method']);
		update_option('dlv_cleanup_notifications_enabled', $validated['email_notifications'] ?? false);
		update_option(LogCleanupService::OPTION_RETENTION_PERCENT, $validated['retention_percentage']);

		// Handle cron scheduling
		$cleanup_controller = new CleanupController();

		if ($validated['cleanup_enabled']) {
			$schedule = isset($_POST['cleanup_schedule']) ? sanitize_text_field(wp_unslash($_POST['cleanup_schedule'])) : 'daily';
			$cleanup_controller->schedule_cleanup($schedule);
		} else {
			$cleanup_controller->unschedule_cleanup();
		}

		wp_send_json_success(array(
			'message' => __('Cleanup settings saved', 'debug-log-viewer'),
			'settings' => $validated,
		));
	}
}
