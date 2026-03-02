<?php

namespace DebugLogViewer\Admin\Controllers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Controllers\LogController;
use DebugLogViewer\Admin\Controllers\LiveUpdatesController;
use DebugLogViewer\Admin\Services\LogCleanupService;
use DebugLogViewer\Admin\Models\LogModel;

class ServiceController
{

	/**
	 * Get all scheduled event option names matching pattern
	 */
	private static function getScheduledEventOptions($pattern = null)
	{
		global $wpdb;

		if ($pattern === null) {
			$pattern = strtoupper(LogController::SCHEDULE_MAIL_SEND . '_user_%');
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
	}

	private static function cleanupScheduledEvents()
	{
		$options = self::getScheduledEventOptions();

		foreach ($options as $option) {
			(new LogController())->deleteWpScheduleEvent($option->option_name);
		}

		wp_clear_scheduled_hook(LogController::SCHEDULE_MAIL_SEND);
		delete_option(LiveUpdatesController::DEBUG_LOG_LAST_FILESIZE);
	}

	/**
	 * Delete multiple options from database
	 */
	private static function deleteOptions(array $options)
	{
		foreach ($options as $option) {
			delete_option($option);
		}
	}

	public static function deactivationHandler()
	{
		self::cleanupScheduledEvents();
		CleanupController::deactivate();
	}

	public static function uninstallHandler()
	{
		self::cleanupScheduledEvents();
		CleanupController::deactivate();

		// Delete all plugin options using constants
		$cleanup_options = [
			LogCleanupService::OPTION_MAX_LOG_SIZE,
			LogCleanupService::OPTION_CLEANUP_ENABLED,
			LogCleanupService::OPTION_CLEANUP_METHOD,
			LogCleanupService::OPTION_LAST_CLEANUP_TIME,
			LogCleanupService::OPTION_LAST_CLEANUP_SIZE,
			LogCleanupService::OPTION_RETENTION_PERCENT,
			LogCleanupService::OPTION_CLEANUP_SCHEDULE,
			LogCleanupService::OPTION_CLEANUP_NOTIFICATIONS,
			LogCleanupService::OPTION_CLEANUP_HISTORY,
			LogCleanupService::OPTION_CLEANUP_NEXT_RUN,
		];

		$log_viewer_options = [
			LogModel::LAST_POSITION_OPTION_NAME,
			LogModel::LOG_UPDATES_MODE_OPTION_NAME,
			LogModel::DATETIME_FORMAT_OPTION_NAME,
			LiveUpdatesController::DEBUG_LOG_LAST_FILESIZE,
		];

		self::deleteOptions(array_merge($cleanup_options, $log_viewer_options));

		// Delete user-specific alert notification options (pattern: SCHEDULE_MAIL_SEND_user_*)
		$options = self::getScheduledEventOptions();
		foreach ($options as $option) {
			delete_option($option->option_name);
		}

		// Delete user meta (timezone stored per-user)
		delete_metadata('user', 0, 'dbg_lv_timezone', '', true);
	}
}
