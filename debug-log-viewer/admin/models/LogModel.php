<?php

namespace DebugLogViewer\Admin\Models;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Translations\Phrases;
use DebugLogViewer\Admin\Helpers\Utils;

class LogModel
{

	// Log level constants
	const LOG_LEVEL_NOTICE     = 'Notice';
	const LOG_LEVEL_WARNING    = 'Warning';
	const LOG_LEVEL_FATAL      = 'Fatal';
	const LOG_LEVEL_DATABASE   = 'Database';
	const LOG_LEVEL_PARSE      = 'Parse';
	const LOG_LEVEL_DEPRECATED = 'Deprecated';
	const LOG_LEVEL_CUSTOM     = 'Custom';

	const LAST_POSITION_OPTION_NAME        = 'dbg_lv_log_last_position';
	const LOG_UPDATES_MODE_OPTION_NAME     = 'dbg_lv_log_updates_mode';
	const DATETIME_FORMAT_OPTION_NAME      = 'dbg_lv_datetime_format';
	const TIMEZONE_OPTION_NAME             = 'dbg_lv_timezone';
	const GROUP_ENTRIES_OPTION_NAME        = 'dbg_lv_group_entries';
	const AUTO_REFRESH_INTERVAL_OPTION     = 'dbg_lv_auto_refresh_interval';
	const SCROLL_MODE_OPTION_NAME          = 'dbg_lv_scroll_mode';
	const SCROLL_MODE_PAGINATION           = 'PAGINATION';
	const SCROLL_MODE_INFINITE             = 'INFINITE';
	const DATETIME_FORMAT_ABSOLUTE         = 'ABSOLUTE';
	const DATETIME_FORMAT_RELATIVE         = 'RELATIVE';
	const LOG_FILE_LIMIT                   = 10 * 1024 * 1024; // 10 MB
	const DEFAULT_AUTO_REFRESH_INTERVAL    = 5;  // 5 seconds

	// File size units constants
	const UNIT_BYTES      = 'bytes';
	const UNIT_KILOBYTES = 'kilobytes';
	const UNIT_MEGABYTES = 'megabytes';
	const UNIT_GIGABYTES = 'gigabytes';

	public static function isCustomLoggingPath() {
		return defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && !in_array(WP_DEBUG_LOG, [ '1', '0', 'true', 'false' ], true) && !empty(WP_DEBUG_LOG);
	}

	public function getLogFilePath() {
		return self::isCustomLoggingPath()
			? WP_DEBUG_LOG
			: WP_CONTENT_DIR . '/debug.log';
	}

	public function getWpConfigPath() {
		// Starting from the current directory
		$dir = __DIR__;

		// Traverse up to 10 levels to avoid infinite loops
		for ($i = 0; $i < 10; $i++) {
			if (file_exists($dir . '/wp-config.php')) {
				return realpath($dir . '/wp-config.php');
			}
			// Move up one directory level
			$dir = dirname($dir);
		}
		return 'wp-config.php not found!';
	}

	public function getLogLimit($unit = self::UNIT_MEGABYTES, $with_units = false) {
		// Validate unit parameter
		$allowed_units = [
			self::UNIT_BYTES,
			self::UNIT_KILOBYTES,
			self::UNIT_MEGABYTES,
			self::UNIT_GIGABYTES
		];

		if (!in_array($unit, $allowed_units, true)) {
			// Fallback to default unit if invalid value provided
			$unit = self::UNIT_MEGABYTES;
		}

		$limit_in_bytes = defined('DBG_LV_USER_DEFINED_LOG_FILE_LIMIT')
			? constant('DBG_LV_USER_DEFINED_LOG_FILE_LIMIT')
			: static::LOG_FILE_LIMIT;

		$converted_value = $this->convertBytesToUnit($limit_in_bytes, $unit);

		if ($with_units) {
			$phrases = Phrases::getParsedContentPhrases();
			$unit_key = $unit;
			$unit_label = isset($phrases[$unit_key]) ? $phrases[$unit_key] : $unit;
			return $converted_value . ' ' . $unit_label;
		}

		return $converted_value;
	}

	public function getInitialLogPosition() {
		// Calculate the initial log position based on the file size and the log limit

		$initial_position = $this->getLogSize([ 'raw' => true ]) - $this->getLogLimit(self::UNIT_BYTES);

		return $initial_position > 0 ? $initial_position : 0;
	}

	private function convertBytesToUnit($bytes, $unit) {
		switch ($unit) {
			case self::UNIT_BYTES:
				return $bytes;
			case self::UNIT_KILOBYTES:
				return round($bytes / 1024, 2);
			case self::UNIT_MEGABYTES:
				return round($bytes / (1024 * 1024), 2);
			case self::UNIT_GIGABYTES:
				return round($bytes / (1024 * 1024 * 1024), 2);
			default:
				return $bytes;
		}
	}

	public function isOverlimited() {
		return $this->getLogSize([ 'raw' => true ]) > $this->getLogLimit(self::UNIT_BYTES);
	}

	private function readFromFileEnd($file_handle, $limit) {
		fseek($file_handle, -$limit, SEEK_END);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$content = fread($file_handle, $limit);
		return $content;
	}

	public function getRawContent( $filename ) {
		if ($this->isOverlimited()) {
			$actual_limit = $this->getLogLimit(self::UNIT_BYTES);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$file_handle  = fopen($filename, 'r');
			$content = $this->readFromFileEnd($file_handle, $actual_limit);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($file_handle);
			return $content;
		} else {
			return file_get_contents($filename);
		}
	}

	public function getNewEntries() {
		$path = $this->getLogFilePath();
		// Handle missing file
		if (!file_exists($path)) {
			return $this->resetLog();
		}

		$file_size = filesize($path);
		// Handle empty file
		if ($file_size === 0) {
			return $this->resetLog();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$file_handle = fopen($path, 'r');
		if (!$file_handle) {
			return $this->resetLog();
		}
		$last_position = get_option(self::LAST_POSITION_OPTION_NAME, 0);

		// Handle new or truncated content
		if ($last_position === 0 || $file_size > $last_position) {
			fseek($file_handle, $last_position, SEEK_SET);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$content      = fread($file_handle, $file_size - $last_position);
			$new_position = ftell($file_handle);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($file_handle);

			update_option(self::LAST_POSITION_OPTION_NAME, $new_position);

			return [
				'action' => [],
				'data'   => $this->splitLogToRows($content),
			];
		}

		// Handle file truncation
		if ($file_size < $last_position) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose($file_handle);
			return $this->resetLog();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose($file_handle);
	}

	private static function resetLog() {
		update_option(self::LAST_POSITION_OPTION_NAME, 0);
		return [
			'action' => 'clear',
			'data'   => [],
		];
	}

	public function getParsedContent() {
		$path = $this->getLogFilePath();

		if (!file_exists($path) || !is_file($path)) {
			return false;
		}

		$content = $this->getRawContent($path);
		return $this->splitLogToRows($content);
	}

	private function splitLogToRows( $content ) {
		$pattern = '/\[[^\]]+\].*?(?=\n\[|$)/s';
		$count   = preg_match_all($pattern, $content, $matches);

		if (!$count) {
			return [];
		}

		return array_reverse($matches[0]);
	}

	public static function getDatetime( $row ) {
		preg_match_all('/\[(.*?)\]/m', $row, $matches, PREG_SET_ORDER, 0);
		return isset($matches[0][1]) ? $matches[0][1] : '';
	}

	public static function getDateTimeZone( $timezone_string = '' ) {
		if ( empty( $timezone_string ) ) {
			return wp_timezone();
		}
		
		try {
			return new \DateTimeZone( $timezone_string );
		} catch ( \Exception $e ) {
			if ( preg_match( '/^UTC([+-])?([0-9]+(?:\.[0-9]+)?)$/', $timezone_string, $matches ) ) {
				$offset = (float) $matches[2];
				if ( isset( $matches[1] ) && '-' === $matches[1] ) {
					$offset = -$offset;
				}
				
				$hours   = (int) $offset;
				$minutes = abs( ( $offset - $hours ) * 60 );
				$sign    = $offset >= 0 ? '+' : '-';
				
				$offset_string = sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
				
				try {
					return new \DateTimeZone( $offset_string );
				} catch ( \Exception $e2 ) {
					return false;
				}
			}
			return false;
		}
	}

	public static function formatDatetimeWithTimezone( $datetime ) {
		if ( empty( $datetime ) ) {
			return $datetime;
		}

		$selected_timezone = get_user_meta( get_current_user_id(), self::TIMEZONE_OPTION_NAME, true );
		$timezone_string   = $selected_timezone ?: wp_timezone_string();

		try {
			$dt = new \DateTime( $datetime );
			
			$target_timezone = self::getDateTimeZone( $timezone_string );
			if ( false === $target_timezone ) {
				return $datetime;
			}
			
			$dt->setTimezone( $target_timezone );
			
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			
			return $dt->format( $date_format . ' ' . $time_format );
		} catch ( \Exception $e ) {
			return $datetime;
		}
	}

	public static function getLine( $row ) {
		preg_match_all('/(on line |php:)(\d{1,})/m', $row, $matches, PREG_SET_ORDER, 0);
		return isset($matches[0][2]) ? $matches[0][2] : '';
	}

	public static function getFile( $row ) {
		preg_match_all('/ in ' . preg_quote(Utils::getDocumentRoot(), '/') . '(.*?)( on line |:)\d{1,}/m', $row, $matches, PREG_SET_ORDER, 0);
		return isset($matches[0][1]) ? $matches[0][1] : '';
	}

	public static function getType( $row ) {
		if (strpos($row, 'PHP Notice:') !== false) {
			return self::LOG_LEVEL_NOTICE;
		} elseif (strpos($row, 'PHP Warning:') !== false) {
			return self::LOG_LEVEL_WARNING;
		} elseif (strpos($row, 'PHP Fatal error:') !== false) {
			return self::LOG_LEVEL_FATAL;
		} elseif (strpos($row, 'WordPress database error') !== false) {
			return self::LOG_LEVEL_DATABASE;
		} elseif (strpos($row, 'PHP Parse error:') !== false) {
			return self::LOG_LEVEL_PARSE;
		} elseif (strpos($row, 'PHP Deprecated:') !== false) {
			return self::LOG_LEVEL_DEPRECATED;
		} else {
			return self::LOG_LEVEL_CUSTOM;
		}
	}

	public static function getStackTrace( $row ) {
		$re = '/Stack trace:\n(.*?)thrown in/s';
		preg_match_all($re, $row, $matches, PREG_SET_ORDER, 0);
		if (isset($matches[0])) {
			return $matches[0][1];
		}
		return null;
	}

	/**
	 * Build a hash key from type, description, file and line.
	 *
	 * @param string $type        Log level / type.
	 * @param string $description Description text.
	 * @param string $file        Source file path.
	 * @param string $line        Line number.
	 * @return string MD5 hash.
	 */
	public static function buildEntryHash( string $type, string $description, string $file, string $line ): string {
		return md5( $type . '::' . $description . '::' . $file . '::' . $line );
	}

	/**
	 * Group formatted log entries by a hash of type + description + file + line.
	 *
	 * @param array $entries Formatted entry arrays (as returned by LiveUpdatesController::getUpdates).
	 * @return array Grouped entries, each with additional `hash` and `count` keys.
	 */
	public static function groupEntries( array $entries ): array {
		$groups = [];

		foreach ($entries as $entry) {
			if (empty($entry)) {
				continue;
			}

			$hash = self::buildEntryHash(
				$entry['type'] ?? '',
				$entry['description']['text'] ?? '',
				$entry['file'] ?? '',
				$entry['line'] ?? ''
			);

			if (isset($groups[ $hash ])) {
				$groups[ $hash ]['count']++;

				// Keep most-recent timestamp / datetime as the representative value.
				if ($entry['timestamp'] > $groups[ $hash ]['timestamp']) {
					$groups[ $hash ]['timestamp'] = $entry['timestamp'];
					$groups[ $hash ]['datetime']  = $entry['datetime'];
				}
			} else {
				$entry['hash']   = $hash;
				$entry['count']  = 1;
				$groups[ $hash ] = $entry;
			}
		}

		return array_values($groups);
	}

	public static function getDescription( $row ) {
		if (self::getType($row) === self::LOG_LEVEL_DATABASE) {

			$re = '/WordPress database error (.*)/m';
			preg_match_all($re, $row, $matches, PREG_SET_ORDER, 0);
			return isset($matches[0]) && $matches[0][1] ? $matches[0][1] : __('N/A', 'debug-log-viewer');
		}

		$re = '/ (PHP Notice:|PHP Warning:|PHP Fatal error:|PHP Parse error:|PHP Deprecated:)(.*?)(\[ | in |on line)/m';
		preg_match_all($re, $row, $matches, PREG_SET_ORDER, 0);
		return isset($matches[0]) && $matches[0][2] ? $matches[0][2] : trim(str_replace('[' . self::getDatetime($row) . ']', '', $row));
	}

	public function getLogSize( $params ) {
		$withUnits = isset($params['with_measure_units']) ? $params['with_measure_units'] : null;
		$raw       = isset($params['raw']) ? $params['raw'] : null;

		$path = $this->getLogFilePath();

		if (is_file($path) && filesize($path)) {
			$filesizeInBytes = filesize($path);
			if ($raw) {
				return $filesizeInBytes;
			}
			$filesizeInMegabytes = $filesizeInBytes / 1024 / 1024;
			return $withUnits
				? round($filesizeInMegabytes, 2) . ' ' . __('Mb', 'debug-log-viewer')
				: round($filesizeInMegabytes, 2);
		} else {
			return 0;
		}
	}

	/**
	 * Write content to log file safely
	 *
	 * @param string $filename File path
	 * @param string $content Content to write
	 * @param bool $append Whether to append or truncate
	 * @return array Result with success status and message
	 */
	public function writeToFile($filename, $content, $append = false) {
		if (!file_exists($filename)) {
			return [
				'success' => false,
				'message' => 'File does not exist: ' . $filename,
			];
		}

		if (!is_writable($filename)) {
			return [
				'success' => false,
				'message' => 'File is not writable: ' . $filename,
			];
		}

		$mode = $append ? 'a' : 'w';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen($filename, $mode);

		if (false === $handle) {
			return [
				'success' => false,
				'message' => 'Failed to open file for writing',
			];
		}

		$bytes_written = fwrite($handle, $content);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose($handle);

		if (false === $bytes_written) {
			return [
				'success' => false,
				'message' => 'Failed to write to file',
			];
		}

		return [
			'success' => true,
			'bytes_written' => $bytes_written,
		];
	}

	/**
	 * Copy file safely with error handling
	 *
	 * @param string $source Source file path
	 * @param string $destination Destination file path
	 * @return array Result with success status and message
	 */
	public function copyFile($source, $destination) {
		if (!file_exists($source)) {
			return [
				'success' => false,
				'message' => 'Source file does not exist: ' . $source,
			];
		}

		if (!is_readable($source)) {
			return [
				'success' => false,
				'message' => 'Source file is not readable: ' . $source,
			];
		}

		try {
			if (!copy($source, $destination)) {
				return [
					'success' => false,
					'message' => 'Failed to copy file',
				];
			}

			// Set proper permissions on the copied file
			$chmod_result = chmod($destination, 0644);
			if (!$chmod_result) {
				// Log warning but don't fail the operation
				error_log('Debug Log Viewer: Warning - Failed to set permissions on copied file: ' . $destination);
			}

			return [
				'success' => true,
				'destination' => $destination,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'message' => 'Exception during file copy: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Delete file safely with error handling
	 *
	 * @param string $filename File path to delete
	 * @return array Result with success status and message
	 */
	public function deleteFile($filename) {
		if (!file_exists($filename)) {
			return [
				'success' => true,
				'message' => 'File does not exist (already deleted)',
			];
		}

		if (!is_writable($filename)) {
			return [
				'success' => false,
				'message' => 'File is not writable/deletable: ' . $filename,
			];
		}

		if (wp_delete_file($filename)) {
			return [
				'success' => true,
				'message' => 'File deleted successfully',
			];
		} else {
			return [
				'success' => false,
				'message' => 'Failed to delete file: ' . $filename,
			];
		}
	}

	/**
	 * Set file permissions safely
	 *
	 * @param string $filename File path
	 * @param int $permissions Octal permissions (e.g., 0644)
	 * @return array Result with success status and message
	 */
	public function setPermissions($filename, $permissions = 0644) {
		if (!file_exists($filename)) {
			return [
				'success' => false,
				'message' => 'File does not exist: ' . $filename,
			];
		}

		if (chmod($filename, $permissions)) {
			return [
				'success' => true,
				'permissions' => decoct($permissions),
			];
		} else {
			return [
				'success' => false,
				'message' => 'Failed to set permissions on file: ' . $filename,
			];
		}
	}
}
