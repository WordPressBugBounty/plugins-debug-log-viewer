<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Models\LogModel;
use DebugLogViewer\Admin\Controllers\FreemiusController;

// Safely validate tab parameter to prevent XSS
$dbg_lv_settings_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
$dbg_lv_is_settings_active = $dbg_lv_settings_tab === 'settings';

$dbg_lv_selected_mode = get_option(LogModel::LOG_UPDATES_MODE_OPTION_NAME, 'MANUAL');
if (empty($dbg_lv_selected_mode)) {
	$dbg_lv_selected_mode = 'MANUAL';
}

$dbg_lv_selected_datetime_format = get_option(LogModel::DATETIME_FORMAT_OPTION_NAME, 'ABSOLUTE');
if (empty($dbg_lv_selected_datetime_format)) {
	$dbg_lv_selected_datetime_format = 'ABSOLUTE';
}

$dbg_lv_auto_refresh_interval = (int) get_option(LogModel::AUTO_REFRESH_INTERVAL_OPTION, LogModel::DEFAULT_AUTO_REFRESH_INTERVAL);

$dbg_lv_current_timezone = get_user_meta(get_current_user_id(), LogModel::TIMEZONE_OPTION_NAME, true);
$dbg_lv_wp_timezone      = wp_timezone_string();
$dbg_lv_display_timezone = $dbg_lv_current_timezone ?: $dbg_lv_wp_timezone;

$dbg_lv_group_entries = get_user_meta(get_current_user_id(), LogModel::GROUP_ENTRIES_OPTION_NAME, true);
$dbg_lv_group_entries = $dbg_lv_group_entries !== '' ? $dbg_lv_group_entries : '1';

$dbg_lv_scroll_mode       = get_option(LogModel::SCROLL_MODE_OPTION_NAME, LogModel::SCROLL_MODE_PAGINATION);
$dbg_lv_is_pro_for_scroll = FreemiusController::is_premium();
?>

<div class="tab-pane fade <?php echo $dbg_lv_is_settings_active ? 'show active' : ''; ?>"
	id="settings-content"
	role="tabpanel"
	aria-labelledby="settings-tab">

	<div class="container">
		<div class="settings section">
			<form class="settings-form" id="dbg_lv_log_viewer_settings_form" onsubmit="return false">

				<!-- ====== Section: Log Updates ====== -->
				<div class="settings-section">
					<div class="settings-cards-grid">

						<!-- Log update mode -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-solid fa-rotate"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Log update mode', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Choose how logs are updated in real-time. Auto mode refreshes at a set interval.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<div class="pill-radio-group">
										<label class="pill-radio <?php echo $dbg_lv_selected_mode === 'MANUAL' ? 'active' : ''; ?>">
											<input type="radio"
												name="UpdatesModeRadioOptions"
												value="<?php echo esc_attr('MANUAL'); ?>"
												id="UpdatesModeRadioOptionsManual"
												<?php checked($dbg_lv_selected_mode, 'MANUAL'); ?>>
											<?php esc_html_e('Manual', 'debug-log-viewer'); ?>
										</label>
										<label class="pill-radio <?php echo $dbg_lv_selected_mode === 'AUTO' ? 'active' : ''; ?>">
											<input type="radio"
												name="UpdatesModeRadioOptions"
												value="<?php echo esc_attr('AUTO'); ?>"
												id="UpdatesModeRadioOptionsAuto"
												<?php checked($dbg_lv_selected_mode, 'AUTO'); ?>>
											<?php esc_html_e('Auto', 'debug-log-viewer'); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- Auto refresh interval -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-regular fa-clock"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Auto refresh interval', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Set how often logs are updated automatically. Lower intervals use more server resources.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<select name="dbg_lv_auto_refresh_interval" id="dbg_lv_auto_refresh_interval" class="settings-select">
										<option value="5" <?php selected($dbg_lv_auto_refresh_interval, 5); ?>><?php esc_html_e('5 seconds', 'debug-log-viewer'); ?></option>
										<option value="10" <?php selected($dbg_lv_auto_refresh_interval, 10); ?>><?php esc_html_e('10 seconds', 'debug-log-viewer'); ?></option>
										<option value="15" <?php selected($dbg_lv_auto_refresh_interval, 15); ?>><?php esc_html_e('15 seconds', 'debug-log-viewer'); ?></option>
										<option value="30" <?php selected($dbg_lv_auto_refresh_interval, 30); ?>><?php esc_html_e('30 seconds', 'debug-log-viewer'); ?></option>
										<option value="60" <?php selected($dbg_lv_auto_refresh_interval, 60); ?>><?php esc_html_e('60 seconds', 'debug-log-viewer'); ?></option>
									</select>
								</div>
							</div>
						</div>

					</div>
				</div>

				<!-- ====== Section: Log Display ====== -->
				<div class="settings-section">
					<div class="settings-cards-grid">

						<!-- Datetime format -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-regular fa-calendar"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Datetime format in logs', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Choose how dates are displayed. Relative shows time differences from now.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<div class="pill-radio-group">
										<label class="pill-radio <?php echo $dbg_lv_selected_datetime_format === 'ABSOLUTE' ? 'active' : ''; ?>">
											<input type="radio"
												name="DatetimeFormatRadioOptions"
												value="<?php echo esc_attr('ABSOLUTE'); ?>"
												id="DatetimeFormatRadioOptionsAbsolute"
												<?php checked($dbg_lv_selected_datetime_format, 'ABSOLUTE'); ?>>
											<?php esc_html_e('Absolute', 'debug-log-viewer'); ?>
										</label>
										<label class="pill-radio <?php echo $dbg_lv_selected_datetime_format === 'RELATIVE' ? 'active' : ''; ?>">
											<input type="radio"
												name="DatetimeFormatRadioOptions"
												value="<?php echo esc_attr('RELATIVE'); ?>"
												id="DatetimeFormatRadioOptionsRelative"
												<?php checked($dbg_lv_selected_datetime_format, 'RELATIVE'); ?>>
											<?php esc_html_e('Relative', 'debug-log-viewer'); ?>
										</label>
									</div>
								</div>
							</div>
						</div>

						<!-- Timezone -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-solid fa-globe"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Timezone for log display', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Select the timezone for displaying log times. Affects how timestamps are converted.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<select name="dbg_lv_timezone" id="dbg_lv_timezone" class="settings-select">
										<?php echo wp_timezone_choice($dbg_lv_display_timezone); ?>
									</select>
								</div>
							</div>
						</div>

						<!-- Log view mode (Pagination / Infinite Scroll) -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-solid fa-table-list"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Log view mode', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Switch between paginated view and a continuous infinite scroll for a more natural log reading experience.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<?php if ($dbg_lv_is_pro_for_scroll) : ?>
										<div class="pill-radio-group">
											<label class="pill-radio <?php echo $dbg_lv_scroll_mode === LogModel::SCROLL_MODE_PAGINATION ? 'active' : ''; ?>">
												<input type="radio"
													name="ScrollModeRadioOptions"
													value="<?php echo esc_attr(LogModel::SCROLL_MODE_PAGINATION); ?>"
													<?php checked($dbg_lv_scroll_mode, LogModel::SCROLL_MODE_PAGINATION); ?>>
												<?php esc_html_e('Pagination', 'debug-log-viewer'); ?>
											</label>
											<label class="pill-radio <?php echo $dbg_lv_scroll_mode === LogModel::SCROLL_MODE_INFINITE ? 'active' : ''; ?>">
												<input type="radio"
													name="ScrollModeRadioOptions"
													value="<?php echo esc_attr(LogModel::SCROLL_MODE_INFINITE); ?>"
													<?php checked($dbg_lv_scroll_mode, LogModel::SCROLL_MODE_INFINITE); ?>>
												<?php esc_html_e('Infinite Scroll', 'debug-log-viewer'); ?>
											</label>
										</div>
									<?php else : ?>
										<div class="d-flex align-items-center gap-2">
											<div class="pill-radio-group">
												<span class="pill-radio active"><?php esc_html_e('Pagination', 'debug-log-viewer'); ?></span>
												<span class="pill-radio"><?php esc_html_e('Infinite Scroll', 'debug-log-viewer'); ?></span>
											</div>
											<span class="pro-badge freemius-checkout-trigger" title="<?php esc_attr_e('Upgrade to Pro', 'debug-log-viewer'); ?>">Pro</span>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>

					</div>
				</div>

				<!-- ====== Section: Data Management ====== -->
				<div class="settings-section">
					<div class="settings-cards-grid">

						<!-- Group duplicates -->
						<div class="settings-card">
							<div class="settings-card-body">
								<div class="settings-card-icon-wrap">
									<i class="fa-regular fa-copy"></i>
								</div>
								<div class="settings-card-info">
									<div class="settings-card-title"><?php esc_html_e('Group duplicates', 'debug-log-viewer'); ?></div>
									<div class="settings-card-desc"><?php esc_html_e('Group identical log entries to reduce clutter.', 'debug-log-viewer'); ?></div>
								</div>
								<div class="settings-card-control">
									<label class="toggle-switch">
										<input type="checkbox"
											id="GroupEntriesToggle"
											<?php checked($dbg_lv_group_entries, '1'); ?>>
										<span class="toggle-slider"></span>
										<span class="toggle-label">
											<?php echo $dbg_lv_group_entries === '1' ? esc_html__('Enabled', 'debug-log-viewer') : esc_html__('Disabled', 'debug-log-viewer'); ?>
										</span>
									</label>
								</div>
							</div>
						</div>

					</div>
				</div>

			</form>
		</div>
	</div>
</div>
