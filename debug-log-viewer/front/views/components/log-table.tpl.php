<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Models\LogModel;
use DebugLogViewer\Admin\Controllers\FreemiusController;

$dbg_lv_file_exists = file_exists($dbg_lv_path);

// Build file info
$dbg_lv_file_info = [];
if ($dbg_lv_file_exists) {
	$dbg_lv_last_modified = filemtime($dbg_lv_path);
	$dbg_lv_permissions   = substr(sprintf('%o', fileperms($dbg_lv_path)), -4);
	$dbg_lv_file_info     = [
		'fileSize'     => $dbg_lv_log_model->getLogSize(['with_measure_units' => true]),
		'lastModified' => $dbg_lv_last_modified
			? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $dbg_lv_last_modified)
			: __('Unknown', 'debug-log-viewer'),
		'permissions'  => $dbg_lv_permissions . ' (' . (is_readable($dbg_lv_path) ? 'R' : '-') . (is_writable($dbg_lv_path) ? 'W' : '-') . ')',
	];
}

// Build warnings
$dbg_lv_warnings = [];
if ($dbg_lv_file_exists && filesize($dbg_lv_path) > LogModel::LOG_FILE_LIMIT) {
	$dbg_lv_limit      = $dbg_lv_log_model->getLogLimit(LogModel::UNIT_MEGABYTES, true);
	$dbg_lv_warnings[] = [
		'icon'    => 'fa-database',
		'type'    => 'warning',
		'title'   => __('Large File Size', 'debug-log-viewer'),
		/* translators: %1$s: file size limit, %2$s: amount of content displayed */
		'message' => sprintf(__('The file exceeds the %1$s limit, only the last %2$s will be displayed.', 'debug-log-viewer'), $dbg_lv_limit, $dbg_lv_limit),
	];
}

$dbg_lv_severity = 'warning';
foreach ($dbg_lv_warnings as $dbg_lv_w) {
	if ($dbg_lv_w['type'] === 'danger') {
		$dbg_lv_severity = 'danger';
		break;
	}
}
?>

<div class="log-path-display ph-no-capture">
	<div class="log-path-text">
		<span>
			<?php echo esc_html($dbg_lv_path); ?>
		</span>
	</div>

	<div class="log-path-icons">
		<i
			class="fa-solid fa-triangle-exclamation log-warning-trigger severity-<?php echo esc_attr($dbg_lv_severity); ?><?php echo empty($dbg_lv_warnings) ? ' d-none' : ''; ?>"
			data-bs-toggle="collapse"
			data-bs-target="#dbg_lv_warnings_panel"></i>
		<?php if (!empty($dbg_lv_file_info)): ?>
			<i
				class="fa-solid fa-circle-info log-info-trigger"
				data-bs-toggle="collapse"
				data-bs-target="#dbg_lv_info_panel"></i>
		<?php endif; ?>
	</div>
</div>

<div class="log-path-collapse-panels" id="dbg_lv_log_panels_accordion">
	<div class="log-panel log-panel-warnings collapse" id="dbg_lv_warnings_panel" data-bs-parent="#dbg_lv_log_panels_accordion">
		<div class="log-panel-content">
			<?php if (!empty($dbg_lv_warnings)): ?>
				<p class="log-panel-header"><?php esc_html_e('Log File Status & Warnings', 'debug-log-viewer'); ?></p>
				<?php foreach ($dbg_lv_warnings as $dbg_lv_warning): ?>
					<div class="d-flex align-items-start mb-2">
						<i class="fa-solid <?php echo esc_attr($dbg_lv_warning['icon']); ?> <?php echo $dbg_lv_warning['type'] === 'danger' ? 'text-danger' : 'text-warning'; ?> me-2 mt-1"></i>
						<div class="flex-grow-1">
							<strong><?php echo esc_html($dbg_lv_warning['title']); ?></strong>
							<p><?php echo esc_html($dbg_lv_warning['message']); ?></p>
							<hr>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="log-panel log-panel-info collapse" id="dbg_lv_info_panel" data-bs-parent="#dbg_lv_log_panels_accordion">
		<div class="log-panel-content">
			<?php if (!empty($dbg_lv_file_info)): ?>
				<p class="log-panel-header"><?php esc_html_e('Log File Information', 'debug-log-viewer'); ?></p>
				<?php if (!empty($dbg_lv_file_info['fileSize'])): ?>
					<div class="d-flex align-items-center mb-2">
						<i class="fa-solid fa-hdd text-muted me-2"></i>
						<span><strong><?php esc_html_e('File size', 'debug-log-viewer'); ?>:</strong> <?php echo esc_html($dbg_lv_file_info['fileSize']); ?></span>
					</div>
				<?php endif; ?>
				<?php if (!empty($dbg_lv_file_info['lastModified'])): ?>
					<div class="d-flex align-items-center mb-2">
						<i class="fa-solid fa-clock text-muted me-2"></i>
						<span><strong><?php esc_html_e('Last modified', 'debug-log-viewer'); ?>:</strong> <?php echo esc_html($dbg_lv_file_info['lastModified']); ?></span>
					</div>
				<?php endif; ?>
				<?php if (!empty($dbg_lv_file_info['permissions'])): ?>
					<div class="d-flex align-items-center mb-2">
						<i class="fa-solid fa-shield-halved text-muted me-2"></i>
						<span><strong><?php esc_html_e('Permissions', 'debug-log-viewer'); ?>:</strong> <?php echo esc_html($dbg_lv_file_info['permissions']); ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="table-wrapper">
	<div class="right-subheader">
		<div class="togglers-group mt-3 mb-4">
			<!-- Debug Mode -->
			<div class="log-info-block form-switch">
				<input
					id="dbg_lv_toggle_debug_mode"
					type="checkbox"
					<?php checked(WP_DEBUG, true); ?>
					name="debug_mode"
					class="form-check-input" />
				<label class="form-check-label" for="dbg_lv_toggle_debug_mode"><?php esc_html_e('Debug mode', 'debug-log-viewer'); ?></label>
			</div>

			<!-- Debug Scripts -->
			<div class="log-info-block form-switch">
				<input
					id="dbg_lv_toggle_debug_scripts"
					type="checkbox"
					<?php checked(SCRIPT_DEBUG, true); ?>
					name="debug_scripts"
					class="form-check-input" />
				<label class="form-check-label" for="dbg_lv_toggle_debug_scripts"><?php esc_html_e('Debug scripts', 'debug-log-viewer'); ?></label>
			</div>

			<!-- Log in File -->
			<div class="log-info-block form-switch">
				<?php
				$dbg_lv_is_custom_path = LogModel::isCustomLoggingPath();
				$dbg_lv_is_disabled    = $dbg_lv_is_custom_path;
				$dbg_lv_is_checked     = $dbg_lv_is_custom_path || in_array(WP_DEBUG_LOG, [1, true, '1', 'true'], true) ? 'checked' : '';
				?>
				<input
					id="dbg_lv_toggle_log_in_file"
					<?php disabled($dbg_lv_is_disabled); ?>
					type="checkbox"
					<?php echo esc_attr($dbg_lv_is_checked); ?>
					name="log_in_file"
					class="form-check-input" />
				<label class="form-check-label" for="dbg_lv_toggle_log_in_file"><?php esc_html_e('Log in file', 'debug-log-viewer'); ?></label>
			</div>

			<!-- Display Errors -->
			<div class="log-info-block form-switch">
				<input
					id="dbg_lv_toggle_display_errors"
					type="checkbox"
					<?php checked(WP_DEBUG_DISPLAY, true); ?>
					name="display_errors"
					class="form-check-input" />
				<label class="form-check-label" for="dbg_lv_toggle_display_errors"><?php esc_html_e('Display errors', 'debug-log-viewer'); ?></label>
			</div>
		</div>
		<div class="search-input-wrapper">
			<i class="fa-solid fa-magnifying-glass search-icon"></i>
			<input id="dbg_lv_search_input" type="search" aria-label="<?php esc_attr_e('Search in log', 'debug-log-viewer'); ?>" placeholder="<?php esc_attr_e('Search in log...', 'debug-log-viewer') ?>" class="text-input search-input <?php echo FreemiusController::is_premium() ? 'has-two-toggles' : 'has-one-toggle'; ?>">
			<button id="dbg_lv_search_smart" class="search-mode-toggle" data-mode="smart" title="<?php esc_attr_e('Smart search: space-separated words in any order', 'debug-log-viewer'); ?>" aria-label="<?php esc_attr_e('Toggle smart search', 'debug-log-viewer'); ?>">
				<i class="fa-solid fa-font"></i>
			</button>
			<?php if (FreemiusController::is_premium()): ?>
				<button id="dbg_lv_search_regex" class="search-mode-toggle" data-mode="regex" title="<?php esc_attr_e('Regular expression search', 'debug-log-viewer'); ?>" aria-label="<?php esc_attr_e('Toggle regex search', 'debug-log-viewer'); ?>">
					<i class="fa-solid fa-asterisk"></i>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div id="dbg_lv_log-table_wrapper">
		<div class="modal" tabindex="-1">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-body"></div>
				</div>
			</div>
		</div>
		<table id="dbg_lv_log-table" class="display striped" style="width:100%">
			<thead>
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
				</tr>
			</thead>
		</table>

	</div>
</div>