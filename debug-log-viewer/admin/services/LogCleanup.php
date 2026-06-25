<?php

namespace DebugLogViewer\Admin\Services;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Models\LogModel;
use function DebugLogViewer\Admin\Controllers\dbg_lv;


class LogCleanupService
{

	// Maximum log file size in bytes (default: 50MB)
	private $maxLogSize;
	private $logFilePath;
	private $logModel;
	// Percentage of log to keep when truncating (default: 10%)
	private $retentionPercentage = 0.10;

	/**
	 * Option names for WordPress options table
	 */
	const OPTION_MAX_LOG_SIZE              = 'dbg_lv_max_log_size';
	const OPTION_CLEANUP_ENABLED           = 'dbg_lv_cleanup_enabled';
	const OPTION_CLEANUP_METHOD            = 'dbg_lv_cleanup_method';
	const OPTION_LAST_CLEANUP_TIME         = 'dbg_lv_last_cleanup_time';
	const OPTION_LAST_CLEANUP_SIZE         = 'dbg_lv_last_cleanup_size';
	const OPTION_RETENTION_PERCENT         = 'dbg_lv_retention_percentage';
	const OPTION_CLEANUP_SCHEDULE          = 'dbg_lv_cleanup_schedule';
	const OPTION_CLEANUP_NOTIFICATIONS     = 'dbg_lv_cleanup_notifications_enabled';
	const OPTION_CLEANUP_HISTORY           = 'dbg_lv_cleanup_history';
	const OPTION_CLEANUP_NEXT_RUN          = 'dbg_lv_cleanup_next_run';

	/**
	 * Cleanup methods
	 */
	const METHOD_TRUNCATE_KEEP_LAST = 'keep_last';
	const METHOD_CLEAR_FILE         = 'clear';
	const METHOD_ARCHIVE_AND_ROTATE = 'archive';

	/**
	 * Returns a human-readable label for a given cleanup method identifier.
	 *
	 * @param string $method The stored method value.
	 * @return string
	 */
	public static function get_method_label( string $method ): string {
		$labels = [
			self::METHOD_TRUNCATE_KEEP_LAST => __( 'Keep Last %', 'debug-log-viewer' ),
			self::METHOD_CLEAR_FILE         => __( 'Clear File', 'debug-log-viewer' ),
			self::METHOD_ARCHIVE_AND_ROTATE => __( 'Archive & Rotate', 'debug-log-viewer' ),
		];

		return $labels[ $method ] ?? ucwords( str_replace( '_', ' ', $method ) );
	}

	/**
	 * Validation constants for settings
	 */
	const DEFAULT_MAX_LOG_SIZE_MB   = 50;    // Default maximum log file size in megabytes
	const MIN_MAX_LOG_SIZE_MB       = 1;     // Minimum allowed max log size
	const MAX_MAX_LOG_SIZE_MB       = 5000;  // Maximum allowed max log size
	const DEFAULT_RETENTION_PERCENT = 10;    // Default percentage of log to keep when truncating
	const MIN_RETENTION_PERCENT     = 5;     // Minimum retention percentage
	const MAX_RETENTION_PERCENT     = 50;    // Maximum retention percentage

	/**
	 * Backup rotation limits (for Archive & Rotate method)
	 */
	const MAX_BACKUPS_FREE          = 5;     // Maximum backup files for Free plan
	const MAX_BACKUPS_PRO           = 20;    // Maximum backup files for Pro plan

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->logModel = new LogModel();
		$this->logFilePath = $this->logModel->getLogFilePath();

		// Get max log size from options (in MB), convert to bytes
		$max_size_mb         = get_option(self::OPTION_MAX_LOG_SIZE, 50);
		$this->maxLogSize  = $max_size_mb * 1024 * 1024; // Convert MB to bytes

		// Get retention percentage (default 10%)
		$retention_percent           = get_option(self::OPTION_RETENTION_PERCENT, 10);
		$this->retentionPercentage = $retention_percent / 100;
	}

	/**
	 * Main cleanup execution method
	 * 
	 * Checks if cleanup is needed and executes it based on configured method
	 *
	 * @return array Array containing success status and message
	 */
	public function executeCleanup()
	{

		// Check if file exists
		if (! file_exists($this->logFilePath)) {
			return [
				'success' => false,
				'message' => 'Log file does not exist: ' . $this->logFilePath,
			];
		}

		// Check if file is readable and writable
		if (! is_readable($this->logFilePath) || ! is_writable($this->logFilePath)) {
			return [
				'success' => false,
				'message' => 'Log file is not readable or writable: ' . $this->logFilePath,
			];
		}

		// Get current file size
		clearstatcache(true, $this->logFilePath);
		$current_size = filesize($this->logFilePath);

		// Determine configured method before the size check
		$cleanup_method = get_option(self::OPTION_CLEANUP_METHOD, self::METHOD_TRUNCATE_KEEP_LAST);

		// Check if cleanup is needed
		if ($current_size <= $this->maxLogSize) {
			return [
				'success' => true,
				'skipped' => true,
				'method'  => $cleanup_method,
				'message' => __('Log file size is within limits. No cleanup needed.', 'debug-log-viewer'),
				'size'    => $current_size,
			];
		}

		// Perform cleanup based on configured method

		switch ($cleanup_method) {
			case self::METHOD_CLEAR_FILE:
				$result = $this->clearLogFile();
				break;

			case self::METHOD_ARCHIVE_AND_ROTATE:
				$result = $this->archiveAndRotate();
				break;

			case self::METHOD_TRUNCATE_KEEP_LAST:
			default:
				$result = $this->truncateKeepLast();
				break;
		}

		// Update last cleanup metadata
		if ($result['success']) {
			update_option(self::OPTION_LAST_CLEANUP_TIME, current_time('mysql'));
			update_option(self::OPTION_LAST_CLEANUP_SIZE, $current_size);
		}

		return $result;
	}

	/**
	 * Truncate the log file keeping only the last X% of data
	 * 
	 * This method is memory-efficient and uses file streams with fseek
	 * to avoid loading the entire file into memory.
	 *
	 * @return array Result of the operation
	 */
	private function truncateKeepLast()
	{
		$current_size = filesize($this->logFilePath);

		// Calculate how much data to keep
		$bytes_to_keep = (int) ($current_size * $this->retentionPercentage);
		$start_offset  = $current_size - $bytes_to_keep;

		// Open files for reading and writing
		$source_handle = fopen($this->logFilePath, 'r');
		if (false === $source_handle) {
			return array(
				'success' => false,
				'message' => 'Failed to open log file for reading',
			);
		}

		// Create temporary file
		$temp_file = $this->logFilePath . '.tmp';
		$temp_handle = fopen($temp_file, 'w');

		if (false === $temp_handle) {
			fclose($source_handle);
			return [
				'success' => false,
				'message' => 'Failed to create temporary file',
			];
		}

		// Seek to the position where we want to start keeping data
		if (fseek($source_handle, $start_offset) !== 0) {
			fclose($source_handle);
			fclose($temp_handle);
			wp_delete_file($temp_file);
			return [
				'success' => false,
				'message' => 'Failed to seek in log file',
			];
		}

		// Skip partial lines - find the next newline
		$first_line = fgets($source_handle);

		// Write header indicating truncation
		$truncation_notice = sprintf(
			"[%s] === Log file truncated by Debug Log Viewer Auto-Cleanup ===\n",
			gmdate('Y-m-d H:i:s')
		);
		fwrite($temp_handle, $truncation_notice);

		// Copy remaining data in chunks to avoid memory issues
		$chunk_size   = 8192; // 8KB chunks
		$bytes_copied = 0;

		while (! feof($source_handle)) {
			$chunk = fread($source_handle, $chunk_size);
			if (false === $chunk) {
				break;
			}
			fwrite($temp_handle, $chunk);
			$bytes_copied += strlen($chunk);
		}

		// Close handles
		fclose($source_handle);
		fclose($temp_handle);

		// Replace original file with truncated version
		if (! rename($temp_file, $this->logFilePath)) {
			$delete_result = $this->logModel->deleteFile($temp_file);
			if (!$delete_result['success']) {
				error_log('Debug Log Viewer: Failed to cleanup temp file: ' . $temp_file);
			}
			return [
				'success' => false,
				'message' => 'Failed to replace log file with truncated version',
			];
		}

		// Set proper permissions
		$chmod_result = $this->logModel->setPermissions($this->logFilePath, 0644);
		if (!$chmod_result['success']) {
			// Log warning but don't fail the operation
			error_log('Debug Log Viewer: ' . $chmod_result['message']);
		}

		clearstatcache(true, $this->logFilePath);

		return [
			'success'      => true,
			'message'      => 'Log file truncated successfully',
			'old_size'     => $current_size,
			'new_size'     => filesize($this->logFilePath),
			'bytes_kept'   => $bytes_copied,
			'method'       => self::METHOD_TRUNCATE_KEEP_LAST,
			'retention'    => ($this->retentionPercentage * 100) . '%',
		];
	}

	/**
	 * Clear the log file completely
	 * 
	 * Most performance-efficient method for very large files
	 *
	 * @return array Result of the operation
	 */
	private function clearLogFile()
	{
		$current_size = filesize($this->logFilePath);

		// Write header
		$header = sprintf(
			"[%s] === Log file cleared by Debug Log Viewer Auto-Cleanup ===\n",
			gmdate('Y-m-d H:i:s')
		);
		
		$write_result = $this->logModel->writeToFile($this->logFilePath, $header, false);

		if (!$write_result['success']) {
			return [
				'success' => false,
				'message' => $write_result['message'],
			];
		}

		// Set proper permissions
		$chmod_result = $this->logModel->setPermissions($this->logFilePath, 0644);
		if (!$chmod_result['success']) {
			// Log warning but don't fail the operation
			error_log('Debug Log Viewer: ' . $chmod_result['message']);
		}

		clearstatcache(true, $this->logFilePath);

		return [
			'success'  => true,
			'message'  => 'Log file cleared successfully',
			'old_size' => $current_size,
			'new_size' => filesize($this->logFilePath),
			'method'   => 'clear',
		];
	}

	/**
	 * Archive the current log and create a new empty one
	 * 
	 * Creates a backup with timestamp before clearing the log.
	 * Automatically rotates backups to prevent disk space bloat.
	 *
	 * @return array Result of the operation
	 */
	private function archiveAndRotate()
	{
		$current_size = filesize($this->logFilePath);

		// Cleanup old backups before creating new one
		$cleanup_result = $this->cleanupOldBackups();
		if (! $cleanup_result['success']) {
			// Log warning but continue with archive
			error_log('Debug Log Viewer: Failed to cleanup old backups: ' . $cleanup_result['message']);
		}

		// Create archive filename with timestamp
		$archive_name = str_replace('.log', '', basename($this->logFilePath));
		$archive_path = dirname($this->logFilePath) . '/' . $archive_name . '-' . gmdate('Y-m-d-His') . '.log';

		// Copy current log to archive using safe method
		$copy_result = $this->logModel->copyFile($this->logFilePath, $archive_path);
		if (!$copy_result['success']) {
			return [
				'success' => false,
				'message' => 'Failed to create archive: ' . $copy_result['message'],
			];
		}

		// Now clear the original file
		$clear_result = $this->clearLogFile();

		if (! $clear_result['success']) {
			// Cleanup archive if clear failed
			$delete_result = $this->logModel->deleteFile($archive_path);
			if (!$delete_result['success']) {
				error_log('Debug Log Viewer: Failed to cleanup archive after failed clear: ' . $archive_path);
			}
			return $clear_result;
		}

		clearstatcache(true, $this->logFilePath);

		$message = 'Log file archived and rotated successfully';
		if (isset($cleanup_result['deleted_count']) && $cleanup_result['deleted_count'] > 0) {
			$message .= sprintf(' (Removed %d old backup(s))', $cleanup_result['deleted_count']);
		}

		return [
			'success'      => true,
			'message'      => $message,
			'old_size'     => $current_size,
			'new_size'     => filesize($this->logFilePath),
			'archive_path' => $archive_path,
			'method'       => 'archiveAndRotate',
		];
	}

	/**
	 * Cleanup old backup files to prevent disk space issues
	 * 
	 * Keeps only the most recent N backups (5 for Free, 20 for Pro).
	 * Only applies to Archive & Rotate method.
	 *
	 * @return array Result with success status and deleted count
	 */
	private function cleanupOldBackups()
	{
		// Determine the maximum number of backups allowed
		$is_premium = dbg_lv()->is_premium();
		$max_backups = $is_premium ? self::MAX_BACKUPS_PRO : self::MAX_BACKUPS_FREE;

		// Get directory path
		$log_dir = dirname($this->logFilePath);
		$log_basename = str_replace('.log', '', basename($this->logFilePath));

		// Find all backup files matching the pattern: debug-2024-02-24-*.log
		$pattern = $log_dir . '/' . $log_basename . '-????-??-??-??????.log';
		$backup_files = glob($pattern);

		if (false === $backup_files || empty($backup_files)) {
			return [
				'success' => true,
				'message' => 'No backups to cleanup',
				'deleted_count' => 0,
			];
		}

		// Check if we need to cleanup
		if (count($backup_files) <= $max_backups) {
			return [
				'success' => true,
				'message' => sprintf('Backup count (%d) within limit (%d)', count($backup_files), $max_backups),
				'deleted_count' => 0,
			];
		}

		// Sort by modification time (oldest first)
		usort($backup_files, function ($a, $b) {
			return filemtime($a) - filemtime($b);
		});

		// Calculate how many files to delete
		$files_to_delete = array_slice($backup_files, 0, count($backup_files) - $max_backups);
		$deleted_count = 0;
		$errors = [];

		// Delete oldest backups
		foreach ($files_to_delete as $file) {
			if (file_exists($file)) {
				$delete_result = $this->logModel->deleteFile($file);
				if ($delete_result['success']) {
					$deleted_count++;
				} else {
					$errors[] = basename($file) . ': ' . $delete_result['message'];
				}
			}
		}

		if (! empty($errors)) {
			return [
				'success' => false,
				'message' => sprintf('Failed to delete %d backup file(s)', count($errors)),
				'deleted_count' => $deleted_count,
				'errors' => $errors,
			];
		}

		return [
			'success' => true,
			'message' => sprintf('Successfully deleted %d old backup(s)', $deleted_count),
			'deleted_count' => $deleted_count,
		];
	}

	/**
	 * Get cleanup statistics for display
	 *
	 * @return array Statistics about cleanup operations
	 */
	public function get_cleanup_stats()
	{
		$current_size = 0;
		if (file_exists($this->logFilePath)) {
			$current_size = filesize($this->logFilePath);
		}

		return [
			'enabled'               => get_option(self::OPTION_CLEANUP_ENABLED, false),
			'max_size_mb'           => get_option(self::OPTION_MAX_LOG_SIZE, 50),
			'max_size_bytes'        => $this->maxLogSize,
			'current_size_bytes'    => $current_size,
			'current_size_mb'       => round($current_size / 1024 / 1024, 2),
			'percentage_used'       => $this->maxLogSize > 0 ? round(($current_size / $this->maxLogSize) * 100, 2) : 0,
			'cleanup_method'        => get_option(self::OPTION_CLEANUP_METHOD, self::METHOD_TRUNCATE_KEEP_LAST),
			'retention_percentage'  => get_option(self::OPTION_RETENTION_PERCENT, 10),
			'email_notifications'   => get_option(self::OPTION_CLEANUP_NOTIFICATIONS, false),
		];
	}

	/**
	 * Check if cleanup is needed (for manual trigger or preview)
	 *
	 * @return bool True if cleanup is needed
	 */
	public function is_cleanup_needed()
	{
		if (! file_exists($this->logFilePath)) {
			return false;
		}

		$current_size = filesize($this->logFilePath);
		return $current_size > $this->maxLogSize;
	}

	/**
	 * Validate and sanitize cleanup settings
	 *
	 * @param array $settings Settings to validate
	 * @return array Validated settings
	 */
	public static function validate_settings($settings)
	{
		$validated = [];
		$is_premium = dbg_lv()->is_premium();

		// Validate max log size (1MB - 5000MB)
		// Free: Fixed at 50MB, Pro: Configurable 1-5000MB
		if (isset($settings['maxLogSize'])) {
			if ($is_premium) {
				$max_size = absint($settings['maxLogSize']);
				$validated['maxLogSize'] = max(self::MIN_MAX_LOG_SIZE_MB, min(self::MAX_MAX_LOG_SIZE_MB, $max_size));
			} else {
				// Free plan: Force to default 50MB
				$validated['maxLogSize'] = self::DEFAULT_MAX_LOG_SIZE_MB;
			}
		}

		// Validate cleanup enabled
		if (isset($settings['cleanupEnabled'])) {
			$validated['cleanupEnabled'] = (bool) $settings['cleanupEnabled'];
		}

		// Validate cleanup method
		// Free: Only Truncate, Pro: All methods
		if (isset($settings['cleanupMethod'])) {
			if ($is_premium) {
				$allowed_methods = array(
					self::METHOD_TRUNCATE_KEEP_LAST,
					self::METHOD_CLEAR_FILE,
					self::METHOD_ARCHIVE_AND_ROTATE,
				);

				$validated['cleanupMethod'] = in_array($settings['cleanupMethod'], $allowed_methods, true)
					? $settings['cleanupMethod']
					: self::METHOD_TRUNCATE_KEEP_LAST;
			} else {
				// Free plan: Force to Truncate method only
				$validated['cleanupMethod'] = self::METHOD_TRUNCATE_KEEP_LAST;
			}
		}

		// Validate retention percentage (5% - 50%)
		// Free: Fixed at 10%, Pro: Configurable 5-50%
		if (isset($settings['retentionPercentage'])) {
			if ($is_premium) {
				$retention = absint($settings['retentionPercentage']);
				$validated['retentionPercentage'] = max(self::MIN_RETENTION_PERCENT, min(self::MAX_RETENTION_PERCENT, $retention));
			} else {
				// Free plan: Force to default 10%
				$validated['retentionPercentage'] = self::DEFAULT_RETENTION_PERCENT;
			}
		}

		// Validate email notifications (Pro only)
		if (isset($settings['emailNotifications'])) {
			$validated['emailNotifications'] = $is_premium ? (bool) $settings['emailNotifications'] : false;
		}

		return $validated;
	}
}
