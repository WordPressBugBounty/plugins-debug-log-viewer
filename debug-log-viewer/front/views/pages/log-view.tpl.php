<?php

namespace DebugLogViewer\Front\Views\Pages;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Models\LogModel;

$dbg_lv_log_model = new LogModel();
$dbg_lv_path      = $dbg_lv_log_model->getLogFilePath();
?>
<!-- Log View Tab Content -->
<div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'log-view') ? 'show active' : ''; ?>"
	id="log-view-content"
	role="tabpanel"
	aria-labelledby="log-view-tab">

	<div class="container-fluid dbg_lv-log-viewer">
		<div class="main-table content-wrapper">

			<?php
			if (!is_file($dbg_lv_path)) {
				require_once realpath(__DIR__) . '/../components/log-missing-debug-file.tpl.php';
			} else {
				require_once realpath(__DIR__) . '/../components/log-table.tpl.php';
			}
			?>
			
			<?php require_once realpath(__DIR__) . '/../components/modals/display-errors.tpl.php'; ?>

		</div>
	</div>
</div>
