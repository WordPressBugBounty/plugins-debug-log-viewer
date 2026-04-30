<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Ensure required variables are passed
if (!isset($nonce_url_do) || !isset($nonce_url_later) || !isset($nonce_url_never)) {
	return;
}
?>

<div class="dbg-lv-review-banner">
	<div class="review-card">
		<div class="review-content">
			<div class="review-icon">
				<i class="fas fa-star"></i>
			</div>
			<div class="review-message">
				<h4 class="review-title">
					<?php esc_html_e('Enjoying Debug Log Viewer?', 'debug-log-viewer'); ?>
				</h4>
				<p class="review-description">
					<?php esc_html_e("You've been using it for a week — if it's saved you time, a quick ⭐ review takes 30 seconds and helps others find it.", 'debug-log-viewer'); ?>
				</p>
			</div>
		</div>
		
		<div class="review-actions">
			<a href="<?php echo esc_url($nonce_url_do); ?>"
			   data-rate-action="do-rate"
			   class="btn btn-primary review-btn">
				<i class="fas fa-star"></i>
				<?php esc_html_e('Leave a Review', 'debug-log-viewer'); ?>
			</a>
			<div class="review-secondary-actions">
				<a href="<?php echo esc_url($nonce_url_later); ?>" data-rate-action="later" class="review-text-link"><?php esc_html_e('Maybe Later', 'debug-log-viewer'); ?></a>
				<span class="review-separator">·</span>
				<a href="<?php echo esc_url($nonce_url_never); ?>" data-rate-action="never" class="review-text-link"><?php esc_html_e('Never ask again', 'debug-log-viewer'); ?></a>
			</div>
		</div>
	</div>
</div>
