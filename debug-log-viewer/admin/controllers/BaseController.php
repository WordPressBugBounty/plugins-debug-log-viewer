<?php

namespace DebugLogViewer\Admin\Controllers;

if (! defined('ABSPATH')) {
	exit;
}

abstract class BaseController
{
	protected function verifyAjaxRequest($nonce_action = 'ajax_nonce', $capability = 'manage_options')
	{
		$nonce = isset($_POST['wp_nonce']) ? sanitize_text_field(wp_unslash($_POST['wp_nonce'])) : '';
		
		if (! wp_verify_nonce($nonce, $nonce_action)) {
			wp_send_json_error(__('Please refresh the page', 'debug-log-viewer'), 403);
		}

		if (! current_user_can($capability)) {
			wp_send_json_error(__('Insufficient permissions', 'debug-log-viewer'), 403);
		}
	}

	protected function verifyRequest($nonce_action, $capability = 'manage_options', $method = null)
	{
		$method = $method ?: $_SERVER['REQUEST_METHOD'];
		$source = ($method === 'POST') ? $_POST : $_GET;
		
		$nonce = isset($source['_wpnonce']) ? sanitize_text_field(wp_unslash($source['_wpnonce'])) : '';
		
		if (! wp_verify_nonce($nonce, $nonce_action)) {
			wp_die(esc_html__('Security check failed', 'debug-log-viewer'), 403);
		}

		if (! current_user_can($capability)) {
			wp_die(esc_html__('You do not have permission to perform this action', 'debug-log-viewer'), 403);
		}
	}
}
