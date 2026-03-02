<?php

namespace DebugLogViewer\Admin\Helpers;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Utils {

	public static function getDocumentRoot() {
		return isset($_SERVER['DOCUMENT_ROOT'])
			? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT']))
			: '';
	}
}
