<?php
/**
 * Quick features preview modal (simple, no pricing)
 *
 * @package DebugLogViewer
 */

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="modal fade" id="dbg-lv-features-modal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header border-0 pb-2">
				<h5 class="modal-title">
					<i class="fas fa-crown text-warning"></i> <?php esc_html_e('Free vs Pro Features', 'debug-log-viewer'); ?>
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body px-4">
				<p class="text-muted mb-3">
					<?php esc_html_e('Compare what is included in Free and what unlocks in Pro.', 'debug-log-viewer'); ?>
				</p>

				<div class="row g-3 mb-3">
					<div class="col-12 col-md-6">
						<div class="card h-100 border border-secondary">
							<div class="card-body">
								<h6 class="card-title mb-3">
									<i class="fas fa-circle text-secondary me-2"></i><?php esc_html_e('Free', 'debug-log-viewer'); ?>
								</h6>
								<ul class="list-unstyled mb-0 small">
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Real-time log view with preset time filters', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Basic email alerts with default error levels', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Daily auto-cleanup (fixed 50 MB, truncate method)', 'debug-log-viewer'); ?></li>
									<li class="mb-0"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Download and clear log tools', 'debug-log-viewer'); ?></li>
								</ul>
							</div>
						</div>
					</div>

					<div class="col-12 col-md-6">
						<div class="card h-100 border border-warning">
							<div class="card-body">
								<h6 class="card-title mb-3">
									<i class="fas fa-crown text-warning me-2"></i><?php esc_html_e('Pro', 'debug-log-viewer'); ?>
								</h6>
								<ul class="list-unstyled mb-0 small">
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Export logs to CSV', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Custom date range filtering', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Choose all error levels for alerts', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Infinite scroll log view mode', 'debug-log-viewer'); ?></li>
									<li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Advanced cleanup controls (size, schedule, method, retention)', 'debug-log-viewer'); ?></li>
									<li class="mb-0"><i class="fas fa-check text-success me-2"></i><?php esc_html_e('Cleanup history and cleanup email notifications', 'debug-log-viewer'); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>

				<button class="btn btn-primary w-100" id="features-modal-upgrade-btn">
					<?php esc_html_e('View Pricing & Upgrade', 'debug-log-viewer'); ?> <i class="fas fa-arrow-right"></i>
				</button>
			</div>
		</div>
	</div>
</div>
