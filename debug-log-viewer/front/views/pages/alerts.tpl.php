<?php

namespace DebugLogViewer\Front\Views\Pages;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Controllers\AlertController;
use DebugLogViewer\Admin\Models\LogModel;
use DebugLogViewer\Admin\Controllers\FreemiusController;

use function DebugLogViewer\Admin\Controllers\dbg_lv;

// Safely validate tab parameter to prevent XSS
$dbg_lv_current_tab      = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
$dbg_lv_valid_tabs       = ['alerts', 'log-view', 'settings'];
$dbg_lv_is_alerts_active = in_array($dbg_lv_current_tab, $dbg_lv_valid_tabs, true) && $dbg_lv_current_tab === 'alerts';

$dbg_lv_notificator = new AlertController();

$dbg_lv_level_meta = [
	LogModel::LOG_LEVEL_DATABASE   => [
		'icon'  => 'fa-solid fa-database',
		'color' => '#6f42c1',
		'bg'    => '#e2d9f3',
	],
	LogModel::LOG_LEVEL_FATAL      => [
		'icon'  => 'fa-solid fa-triangle-exclamation',
		'color' => '#dc3545',
		'bg'    => '#f8d7da',
	],
	LogModel::LOG_LEVEL_PARSE      => [
		'icon'  => 'fa-solid fa-code',
		'color' => '#0366d6',
		'bg'    => '#cfe8ff',
	],
	LogModel::LOG_LEVEL_DEPRECATED => [
		'icon'  => 'fa-solid fa-triangle-exclamation',
		'color' => '#6c757d',
		'bg'    => '#e2e3e5',
	],
	LogModel::LOG_LEVEL_WARNING    => [
		'icon'  => 'fa-solid fa-circle-exclamation',
		'color' => '#a46200',
		'bg'    => 'rgb(255 213 125 / 88%)',
	],
	LogModel::LOG_LEVEL_NOTICE     => [
		'icon'  => 'fa-solid fa-circle-info',
		'color' => '#28a745',
		'bg'    => '#d4f5e2',
	],
	LogModel::LOG_LEVEL_CUSTOM     => [
		'icon'  => 'fa-solid fa-gear',
		'color' => '#007bff',
		'bg'    => '#d0e7ff',
	],
];
?>

<div class="tab-pane fade <?php echo $dbg_lv_is_alerts_active ? 'show active' : ''; ?>"
	id="alerts-content"
	role="tabpanel"
	aria-labelledby="alerts-tab">

	<div class="container">
		<div class="alerts section">

			<form class="alerts-form" id="dbg_lv_log_viewer_alerts_form" onsubmit="return false"
				data-alerts-enabled="<?php echo esc_attr($dbg_lv_notificator->isEnabled() ? 'true' : 'false'); ?>"
				data-is-premium="<?php echo esc_attr(FreemiusController::is_premium() ? 'true' : 'false'); ?>">

				<?php
				$id      = 'alerts-next-run';
				$dynamic = true;
				include realpath(__DIR__) . '/../../views/components/next-schedule-info.tpl.php';
				?>

				<div class="alerts-layout">

					<!-- ====== Left column ====== -->
					<div class="alerts-col-left">

						<!-- Email recipients -->
						<div class="alerts-card">
							<div class="alerts-card-header">
								<div class="alerts-card-icon-wrap">
									<i class="fa-regular fa-envelope"></i>
								</div>
								<div class="alerts-card-info">
									<div class="alerts-card-title"><?php esc_html_e('Email recipients', 'debug-log-viewer'); ?></div>
									<div class="alerts-card-desc"><?php esc_html_e('Add one or more email addresses to receive alerts.', 'debug-log-viewer'); ?></div>
								</div>
							</div>
							<div class="alerts-card-body">
								<div id="email-recipients-container">
									<?php
									$dbg_lv_emails = $dbg_lv_notificator->getNotificationEmails();
									if (empty($dbg_lv_emails)) {
										$dbg_lv_emails = [''];
									}
									foreach ($dbg_lv_emails as $dbg_lv_index => $dbg_lv_email) :
									?>
										<div class="email-input-row" data-index="<?php echo esc_attr($dbg_lv_index); ?>">
											<input type="email" name="emails[]" value="<?php echo esc_attr($dbg_lv_email); ?>" disabled />
											<?php if ($dbg_lv_index > 0) : ?>
												<button type="button" class="alerts-remove-email-btn remove-email-btn" title="<?php esc_attr_e('Remove this email', 'debug-log-viewer'); ?>" disabled>
													<i class="fa-solid fa-trash"></i>
												</button>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="alerts-email-actions">
									<?php if (FreemiusController::is_premium()) : ?>
										<button type="button" id="alerts-add-email-btn" class="alerts-add-btn" disabled>
											<i class="fa-solid fa-plus"></i>
											<?php esc_html_e('Add email address', 'debug-log-viewer'); ?>
										</button>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Alert frequency -->
						<div class="alerts-card">
							<div class="alerts-card-header">
								<div class="alerts-card-icon-wrap">
									<i class="fa-regular fa-clock"></i>
								</div>
								<div class="alerts-card-info">
									<div class="alerts-card-title"><?php esc_html_e('Alert frequency', 'debug-log-viewer'); ?></div>
									<div class="alerts-card-desc"><?php esc_html_e('Choose how often you want to receive alerts.', 'debug-log-viewer'); ?></div>
								</div>
							</div>
							<div class="alerts-card-body">
								<select name="recurrence" id="recurrence" class="alerts-select" disabled>
									<?php $dbg_lv_notificator->getRecurrence(); ?>
								</select>
							</div>
						</div>

						<!-- Test notifications (shown by JS when alerts are disabled) -->
						<div class="alerts-card alert-test-card">
							<div class="alerts-card-header">
								<div class="alerts-card-icon-wrap">
									<i class="fa-regular fa-paper-plane"></i>
								</div>
								<div class="alerts-card-info">
									<div class="alerts-card-title"><?php esc_html_e('Test notifications', 'debug-log-viewer'); ?></div>
									<div class="alerts-card-desc"><?php esc_html_e('Send a test email to verify your settings are working.', 'debug-log-viewer'); ?></div>
								</div>
							</div>
							<div class="alerts-card-body">
							<button type="button" id="send-test-email-btn" class="alerts-test-email-btn" disabled>
								<i class="fa-regular fa-paper-plane"></i>
								<?php esc_html_e('Send test email', 'debug-log-viewer'); ?>
							</button>
							</div>
						</div>

					</div><!-- /.alerts-col-left -->

					<!-- ====== Right column ====== -->
					<div class="alerts-col-right">

						<div class="alerts-card alerts-notify-card">
							<div class="alerts-card-header">
								<div class="alerts-card-icon-wrap">
									<i class="fa-regular fa-bell"></i>
								</div>
								<div class="alerts-card-info">
									<div class="alerts-card-title"><?php esc_html_e('Notify me about', 'debug-log-viewer'); ?></div>
									<div class="alerts-card-desc"><?php esc_html_e('Select which error levels you want to monitor.', 'debug-log-viewer'); ?></div>
								</div>
							</div>

							<div class="alerts-error-levels <?php echo !FreemiusController::is_premium() ? 'pro-feature-section' : ''; ?>">

								<?php if (!FreemiusController::is_premium()): ?>
									<div class="pro-feature-notice">
										<p class="upgrade-notice">
											<i class="fas fa-rocket"></i>
											<?php esc_html_e('Upgrade to Pro to choose specific error levels', 'debug-log-viewer'); ?>
											<span class="upgrade-link freemius-checkout-trigger"><?php esc_html_e('Upgrade Now', 'debug-log-viewer'); ?></span>
										</p>
									</div>
								<?php endif; ?>

								<div class="togglers-group <?php echo !FreemiusController::is_premium() ? 'pro-disabled' : ''; ?>">
									<?php
									$dbg_lv_available_levels = $dbg_lv_notificator->getAllAvailableLevels();
									$dbg_lv_selected_levels  = $dbg_lv_notificator->getSelectedLevels();

									if (!FreemiusController::is_premium()) {
										$dbg_lv_available_levels = array_slice($dbg_lv_available_levels, 0, 4, true);
									}

									foreach ($dbg_lv_available_levels as $dbg_lv_level => $dbg_lv_label) :
										$dbg_lv_is_checked  = in_array($dbg_lv_level, $dbg_lv_selected_levels);
										$dbg_lv_toggle_id   = 'dbg_lv_toggle_level_' . esc_attr($dbg_lv_level);
										$dbg_lv_level_icon  = isset($dbg_lv_level_meta[$dbg_lv_level]['icon'])  ? $dbg_lv_level_meta[$dbg_lv_level]['icon']  : 'fa-solid fa-circle';
										$dbg_lv_level_color = isset($dbg_lv_level_meta[$dbg_lv_level]['color']) ? $dbg_lv_level_meta[$dbg_lv_level]['color'] : '#6b7280';
									$dbg_lv_level_bg    = isset($dbg_lv_level_meta[$dbg_lv_level]['bg'])    ? $dbg_lv_level_meta[$dbg_lv_level]['bg']    : '#f3f4f6';
									?>
										<div class="alerts-level-row">
											<div class="alerts-level-icon-wrap" style="color: <?php echo esc_attr($dbg_lv_level_color); ?>; background-color: <?php echo esc_attr($dbg_lv_level_bg); ?>">
												<i class="<?php echo esc_attr($dbg_lv_level_icon); ?>"></i>
											</div>
											<div class="alerts-level-info">
												<div class="alerts-level-name"><?php echo esc_html($dbg_lv_label); ?></div>
											</div>
											<label class="toggle-switch alerts-toggle" for="<?php echo esc_attr($dbg_lv_toggle_id); ?>">
												<input type="checkbox"
													id="<?php echo esc_attr($dbg_lv_toggle_id); ?>"
													name="levels[]"
													value="<?php echo esc_attr($dbg_lv_level); ?>"
													class="error-level-checkbox"
													<?php checked($dbg_lv_is_checked, true); ?>
													disabled>
												<span class="toggle-slider"></span>
											</label>
										</div>
									<?php endforeach; ?>

									<?php if (!FreemiusController::is_premium()): ?>
										<div class="alerts-level-row pro-preview">
											<span class="preview-text">
												<?php esc_html_e('+ 3 more error levels available in ', 'debug-log-viewer'); ?>
												<span class="pro-badge freemius-checkout-trigger">Pro</span>
											</span>
										</div>
									<?php endif; ?>
								</div>

							</div><!-- /.alerts-error-levels -->
						</div><!-- /.alerts-notify-card -->

					</div><!-- /.alerts-col-right -->

				</div><!-- /.alerts-layout -->

				<!-- Footer -->
				<div class="alerts-footer">
					<input type="submit" class="btn btn-primary" value="<?php esc_attr_e('Loading...', 'debug-log-viewer'); ?>" disabled />
				</div>

			</form>

		</div><!-- /.alerts.section -->
	</div><!-- /.container -->
</div>