<?php

if (!defined('ABSPATH')) {
    exit;
}

$id = isset($id) ? $id : 'next-schedule-info';
$is_scheduled = isset($is_scheduled) ? $is_scheduled : false;
$next_run_human = isset($next_run_human) ? $next_run_human : '';
$label = isset($label) ? $label : __('Next check:', 'debug-log-viewer');
$class = isset($class) ? $class : '';
$dynamic = isset($dynamic) ? $dynamic : false;
?>

<?php if ($dynamic) : ?>
    <!-- Dynamic updates via JavaScript -->
    <div id="<?php echo esc_attr($id); ?>" class="next-schedule-info hidden<?php echo $class ? ' ' . esc_attr($class) : ''; ?>">
        <p>
            <strong class="next-time"></strong>
            <small class="next-countdown"></small>
        </p>
    </div>
<?php elseif ($is_scheduled && !empty($next_run_human)) : ?>
    <!-- Static server-rendered -->
    <div id="<?php echo esc_attr($id); ?>" class="next-schedule-info<?php echo $class ? ' ' . esc_attr($class) : ''; ?>">
        <p>
            <strong><?php echo esc_html($label); ?></strong>
            <span class="next-run-time"><?php echo esc_html($next_run_human); ?></span>
        </p>
    </div>
<?php elseif ($is_scheduled && empty($next_run_human)) : ?>
    <!-- Debug fallback for empty next_run_human -->
    <div id="<?php echo esc_attr($id); ?>" class="next-schedule-info calculating-state<?php echo $class ? ' ' . esc_attr($class) : ''; ?>">
        <p>
            <strong><?php echo esc_html($label); ?></strong>
            <span class="next-run-time"><?php esc_html_e('Calculating...', 'debug-log-viewer'); ?></span>
        </p>
    </div>
<?php endif; ?>