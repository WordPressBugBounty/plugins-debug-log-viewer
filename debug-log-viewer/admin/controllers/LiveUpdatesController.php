<?php

namespace DebugLogViewer\Admin\Controllers;

use DebugLogViewer\Admin\Models\LogModel;
use \DebugLogViewer\Admin\Helpers\Utils;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

require_once realpath(__DIR__) . '/BaseController.php';

class LiveUpdatesController extends BaseController {


	public const LIVE_UPDATE_INTERVAL    = 5; // seconds
	public const ITERATIONS_PER_SESSION  = 12; // 1 minute
	public const DEBUG_LOG_LAST_FILESIZE = 'dbg_lv_dbg_log_last_filesize';
	public const LOG_UPDATES_INTERVAL    = 10; // seconds

	private $logModel;

	public function __construct( ?LogModel $logModel = null ) {
		$this->logModel = $logModel ?? new LogModel();
	}

	public function run() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->verifyAjaxRequest();

		if (isset($_POST['initial']) && $_POST['initial'] === 'true') {
			update_option(LogModel::LAST_POSITION_OPTION_NAME, $this->logModel->getInitialLogPosition());
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$group_entries = isset($_POST['group_entries']) && $_POST['group_entries'] === 'true';

		$this->clearDebugLogFileStat();
		$updates = $this->logModel->getNewEntries();

		if (isset($updates['data'])) {
			echo $this->getUpdates($updates, $group_entries);
		}

		wp_die();
	}

	public function clearDebugLogFileStat(): void {
		$path = $this->logModel->getLogFilePath();
		if (is_file($path) && file_exists($path)) {
			clearstatcache(true, $path);
		}
	}

	public function getUpdates( $updates, bool $group_entries = true ): string {
		$formatted = array_map(function ( $row ) {
			if (empty($row)) {
				return;
			}
			
			$datetime = LogModel::getDatetime($row);
			
			return [
				'timestamp'   => strtotime($datetime) * 1000,
				'datetime'    => LogModel::formatDatetimeWithTimezone($datetime),
				'line'        => LogModel::getLine($row),
				'file'        => LogModel::getFile($row),
				'type'        => LogModel::getType($row),
				'description' => [
					'text'        => LogModel::getDescription($row),
					'stack_trace' => LogModel::getStackTrace($row),
				],
			];
		}, $updates['data']);

		$entries = array_values(array_filter($formatted));

		if ($group_entries) {
			$entries = LogModel::groupEntries($entries);
		}

		return json_encode([
			'action' => $updates['action'],
			'data'   => $entries,
		]);
	}
}
