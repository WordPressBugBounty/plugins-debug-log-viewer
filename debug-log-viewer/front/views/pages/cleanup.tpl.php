<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use DebugLogViewer\Admin\Services\LogCleanupService;
use DebugLogViewer\Admin\Controllers\CleanupController;
use function DebugLogViewer\Admin\Controllers\dbg_lv;

$cleanup_service = new LogCleanupService();
$cleanup_controller = new CleanupController();

$stats = $cleanup_service->get_cleanup_stats();
$schedule_info = $cleanup_controller->get_schedule_info();
$history = $cleanup_controller->getCleanupHistory(0); // 0 = fetch all entries (up to 50)

// Check if user has premium access
$is_premium = dbg_lv()->is_premium();
?>

<div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'cleanup') ? 'show active' : ''; ?>"
    id="cleanup-content"
    role="tabpanel"
    aria-labelledby="cleanup-tab-btn">

    <div class="container">
        <div class="cleanup section">

            <!-- Header Section -->
            <div class="section-header">
                <p>
                    <?php esc_html_e('Automatically manage your debug.log file size to prevent server crashes and disk space issues.', 'debug-log-viewer'); ?>
                </p>
            </div>

            <!-- Next Scheduled Run Info -->
            <?php
            $id = 'next-cleanup-info';
            $is_scheduled = false; // Will be populated via JavaScript
            $next_run_human = '';
            $label = __('Next check:', 'debug-log-viewer');
            $dynamic = true; // Enable dynamic JavaScript updates
            include plugin_dir_path(__DIR__) . 'components/next-schedule-info.tpl.php';
            ?>

            <!-- Progress Bar -->
            <?php if (isset($stats['percentage_used'])): ?>
                <div class="d-flex justify-content-between mb-1 mt-2">
                    <span><?php esc_html_e('Quota Usage', 'debug-log-viewer'); ?></span>
                    <span><?php echo number_format($stats['current_size_mb'], 2); ?> / <?php echo number_format($stats['max_size_mb'], 0); ?> MB</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <?php
                    $progress_class = $stats['percentage_used'] > 90 ? 'bg-danger' : ($stats['percentage_used'] > 70 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress-bar <?php echo esc_attr($progress_class); ?>"
                        role="progressbar"
                        style="width: <?php echo esc_attr(min($stats['percentage_used'], 100)); ?>%"
                        aria-valuenow="<?php echo esc_attr($stats['percentage_used']); ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <?php echo number_format($stats['percentage_used'], 1); ?>%
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <div class="cleanup section">
            <!-- Configuration Form -->
            <form class="form-group" id="dbg_lv_cleanup_settings_form" data-cleanup-enabled="<?php echo esc_attr($stats['enabled'] ? 'true' : 'false'); ?>">

                <?php if (!$is_premium): ?>
                    <div class="pro-feature-notice">
                        <p class="upgrade-notice">
                            <i class="fas fa-star"></i>
                            <?php esc_html_e('Upgrade to Pro for archival backups (up to 20 copies), custom size limits (1-5000MB), flexible schedules, email notifications, and cleanup history tracking', 'debug-log-viewer'); ?>
                            <span class="upgrade-link freemius-checkout-trigger"><?php esc_html_e('Upgrade Now', 'debug-log-viewer'); ?></span>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <!-- Maximum Log Size -->
                        <div class="settings-group mb-4">
                            <label for="dbg_lv_max_log_size">
                                <?php esc_html_e('Maximum Log Size (MB)', 'debug-log-viewer'); ?>
                                <i class="fas fa-info-circle tooltip-icon"
                                    title="<?php echo $is_premium ? /* translators: %1$d and %2$d: minimum and maximum log size in MB */ sprintf(esc_attr__('Maximum allowed size before cleanup is triggered (%1$d-%2$d MB).', 'debug-log-viewer'), intval(LogCleanupService::MIN_MAX_LOG_SIZE_MB), intval(LogCleanupService::MAX_MAX_LOG_SIZE_MB)) : esc_attr__('Free plan is set to 50MB for optimal performance.', 'debug-log-viewer'); ?>"></i>
                            </label>
                            <?php if ($is_premium): ?>
                                <input type="number"
                                    class="regular-text"
                                    id="dbg_lv_max_log_size"
                                    name="dbg_lv_max_log_size"
                                    value="<?php echo esc_attr($stats['max_size_mb']); ?>"
                                    min="<?php echo esc_attr(LogCleanupService::MIN_MAX_LOG_SIZE_MB); ?>"
                                    max="<?php echo esc_attr(LogCleanupService::MAX_MAX_LOG_SIZE_MB); ?>"
                                    step="1">
                            <?php else: ?>
                                <input type="text"
                                    class="regular-text"
                                    value="50"
                                    readonly
                                    style="background-color: #f9f9f9;">
                                <input type="hidden" id="dbg_lv_max_log_size" name="dbg_lv_max_log_size" value="50">
                            <?php endif; ?>
                        </div>

                        <!-- Cleanup Method -->
                        <div class="settings-group mb-4">
                            <label for="dlv_cleanup_method">
                                <?php esc_html_e('Cleanup Method', 'debug-log-viewer'); ?>
                                <i class="fas fa-info-circle tooltip-icon"
                                    title="<?php echo $is_premium ? esc_attr__('Truncate: Keeps recent logs, memory-efficient. Clear: Removes everything, fastest method. Archive: Saves a backup before clearing.', 'debug-log-viewer') : esc_attr__('Truncate method keeps the most recent log entries while safely reducing file size.', 'debug-log-viewer'); ?>"></i>
                            </label>
                            <?php if ($is_premium): ?>
                                <select class="regular-text" id="dlv_cleanup_method" name="dlv_cleanup_method" data-truncate-method="<?php echo esc_attr(LogCleanupService::METHOD_TRUNCATE_KEEP_LAST); ?>">
                                    <option value="<?php echo esc_attr(LogCleanupService::METHOD_TRUNCATE_KEEP_LAST); ?>"
                                        <?php selected($stats['cleanup_method'], LogCleanupService::METHOD_TRUNCATE_KEEP_LAST); ?>>
                                        <?php esc_html_e('Truncate (Keep Last X%) - Recommended', 'debug-log-viewer'); ?>
                                    </option>
                                    <option value="<?php echo esc_attr(LogCleanupService::METHOD_CLEAR_FILE); ?>"
                                        <?php selected($stats['cleanup_method'], LogCleanupService::METHOD_CLEAR_FILE); ?>>
                                        <?php esc_html_e('Clear File Completely', 'debug-log-viewer'); ?>
                                    </option>
                                    <option value="<?php echo esc_attr(LogCleanupService::METHOD_ARCHIVE_AND_ROTATE); ?>"
                                        <?php selected($stats['cleanup_method'], LogCleanupService::METHOD_ARCHIVE_AND_ROTATE); ?>>
                                        <?php esc_html_e('Archive and Rotate (Creates Backup)', 'debug-log-viewer'); ?>
                                    </option>
                                </select>
                            <?php else: ?>
                                <input type="text"
                                    class="regular-text"
                                    value="<?php esc_attr_e('Truncate (Keep Last 10%)', 'debug-log-viewer'); ?>"
                                    readonly
                                    style="background-color: #f9f9f9;">
                                <input type="hidden" id="dlv_cleanup_method" name="dlv_cleanup_method" value="<?php echo esc_attr(LogCleanupService::METHOD_TRUNCATE_KEEP_LAST); ?>" data-truncate-method="<?php echo esc_attr(LogCleanupService::METHOD_TRUNCATE_KEEP_LAST); ?>">
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="col-md-6">
                        <!-- Schedule Frequency -->
                        <div class="settings-group mb-4">
                            <label for="dlv_cleanup_schedule">
                                <?php esc_html_e('Check Frequency', 'debug-log-viewer'); ?>
                                <i class="fas fa-info-circle tooltip-icon"
                                    title="<?php echo $is_premium ? esc_attr__('How often the system checks if cleanup is needed.', 'debug-log-viewer') : esc_attr__('Free plan checks daily to keep your log file under control.', 'debug-log-viewer'); ?>"></i>
                            </label>
                            <?php if ($is_premium): ?>
                                <select class="regular-text" id="dlv_cleanup_schedule" name="dlv_cleanup_schedule">
                                    <option value="hourly" <?php selected($schedule_info['schedule'], 'hourly'); ?>>
                                        <?php esc_html_e('Hourly', 'debug-log-viewer'); ?>
                                    </option>
                                    <option value="twicedaily" <?php selected($schedule_info['schedule'], 'twicedaily'); ?>>
                                        <?php esc_html_e('Twice Daily', 'debug-log-viewer'); ?>
                                    </option>
                                    <option value="daily" <?php selected($schedule_info['schedule'], 'daily'); ?>>
                                        <?php esc_html_e('Daily', 'debug-log-viewer'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($schedule_info['schedule'], 'weekly'); ?>>
                                        <?php esc_html_e('Weekly', 'debug-log-viewer'); ?>
                                    </option>
                                </select>
                            <?php else: ?>
                                <input type="text"
                                    class="regular-text"
                                    value="<?php esc_attr_e('Daily', 'debug-log-viewer'); ?>"
                                    readonly
                                    style="background-color: #f9f9f9;">
                                <input type="hidden" id="dlv_cleanup_schedule" name="dlv_cleanup_schedule" value="daily">
                            <?php endif; ?>
                        </div>

                        <!-- Retention Percentage -->
                        <?php if ($is_premium): ?>
                            <div class="settings-group mb-4" id="dlv_retention_row">
                                <label for="dlv_retention_percentage">
                                    <?php esc_html_e('Retention Percentage', 'debug-log-viewer'); ?>
                                    <i class="fas fa-info-circle tooltip-icon"
                                        title="<?php /* translators: %1$d and %2$d: minimum and maximum retention percentage */ printf(esc_attr__('Percentage of the log file to keep when truncating (%1$d-%2$d%%). Only applies to Truncate method.', 'debug-log-viewer'), intval(LogCleanupService::MIN_RETENTION_PERCENT), intval(LogCleanupService::MAX_RETENTION_PERCENT)); ?>"></i>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                        class="regular-text"
                                        id="dlv_retention_percentage"
                                        name="dlv_retention_percentage"
                                        value="<?php echo esc_attr($stats['retention_percentage']); ?>"
                                        min="<?php echo esc_attr(LogCleanupService::MIN_RETENTION_PERCENT); ?>"
                                        max="<?php echo esc_attr(LogCleanupService::MAX_RETENTION_PERCENT); ?>"
                                        step="1">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" id="dlv_retention_percentage" name="dlv_retention_percentage" value="10">
                            <div class="settings-group mb-4" id="dlv_retention_row" style="display: none;"></div>
                        <?php endif; ?>

                        <div style="margin-bottom: 1rem;"></div>
                    </div>

                </div>

                <!-- Email Notifications (Pro Only) -->
                <?php if ($is_premium): ?>
                    <div class="settings-group mb-4">
                        <label class="form-check-label">
                            <input class="form-check-input"
                                type="checkbox"
                                name="dlv_email_notifications"
                                id="dlv_email_notifications"
                                value="1"
                                <?php checked($stats['email_notifications'], true); ?>>
                            <span class="form-check-sign"></span>
                            <span><?php esc_html_e('Email Notifications', 'debug-log-viewer'); ?></span>
                            <i class="fas fa-info-circle tooltip-icon"
                                title="<?php esc_attr_e('Send email notification to site admin after each automatic cleanup.', 'debug-log-viewer'); ?>"></i>
                        </label>
                    </div>
                <?php else: ?>
                    <input type="hidden" id="dlv_email_notifications" name="dlv_email_notifications" value="0">
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="form-group mt-4 d-flex justify-content-between">
                    <input type="submit" value="<?php esc_attr_e('Loading...', 'debug-log-viewer'); ?>" class="btn btn-primary btn-sm" id="dbg_lv_save_cleanup_settings" data-nonce="<?php echo esc_attr(wp_create_nonce('dbg_lv_save_cleanup_settings')); ?>" disabled />

                    <?php if ($stats['current_size_bytes'] > 0) : ?>
                        <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            id="dbg_lv_run_cleanup_now"
                            data-cleanup-url="<?php echo esc_url(wp_nonce_url(add_query_arg('dbg_lv_manual_cleanup', '1'), 'dbg_lv_manual_cleanup')); ?>">
                            <i class="fas fa-play me-2"></i>
                            <?php esc_html_e('Run Manually', 'debug-log-viewer'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

        </div>
    </div>

    <!-- Cleanup History Section -->
    <?php if ($is_premium): ?>
        <div class="container">
            <div class="cleanup section">
                <div class="alerts-section">
                    <?php if (!empty($history)) : ?>
                        <?php
                        $total_entries = count($history);
                        $visible_entries = array_slice($history, 0, 10);
                        $hidden_entries = array_slice($history, 10);
                        $hidden_count = count($hidden_entries);
                        ?>
                        <div class="table-responsive">
                            <table id="cleanup-history-table" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Date & Time', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('Status', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('Method', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('Before', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('After', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('Reduction', 'debug-log-viewer'); ?></th>
                                        <th><?php esc_html_e('Message', 'debug-log-viewer'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visible_entries as $entry) : ?>
                                        <tr>
                                            <td>
                                                <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['timestamp']))); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($entry['success']) : ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i>
                                                        <?php esc_html_e('Success', 'debug-log-viewer'); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle"></i>
                                                        <?php esc_html_e('Failed', 'debug-log-viewer'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $method_labels = array(
                                                    'truncateKeepLast' => __('Truncate', 'debug-log-viewer'),
                                                    'clear' => __('Clear', 'debug-log-viewer'),
                                                    'archiveAndRotate' => __('Archive', 'debug-log-viewer'),
                                                    'unknown' => __('Unknown', 'debug-log-viewer'),
                                                );
                                                echo esc_html($method_labels[$entry['method']] ?? $entry['method']);
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (isset($entry['old_size']) && $entry['old_size'] > 0) {
                                                    echo esc_html(size_format($entry['old_size'], 2));
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (isset($entry['new_size']) && $entry['new_size'] > 0) {
                                                    echo esc_html(size_format($entry['new_size'], 2));
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (isset($entry['old_size']) && isset($entry['new_size']) && $entry['old_size'] > 0) {
                                                    $reduction = $entry['old_size'] - $entry['new_size'];
                                                    $reduction_percent = round(($reduction / $entry['old_size']) * 100, 1);
                                                    echo '<span class="text-success">';
                                                    echo esc_html(size_format($reduction, 2));
                                                    echo '(' . esc_html($reduction_percent) . '%)';
                                                    echo '</span>';
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo esc_html($entry['message']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($hidden_count > 0) : ?>
                                        <tr>
                                            <td colspan="7" class="p-0 border-0">
                                                <details class="cleanup-history-details">
                                                    <summary>
                                                        <?php
                                                        /* translators: %d: number of hidden cleanup entries */
                                                        printf(esc_html__('Show %d more entries', 'debug-log-viewer'), intval($hidden_count));
                                                        ?>
                                                        <i class="fas fa-chevron-down ms-2"></i>
                                                    </summary>
                                                    <table class="table table-striped table-hover">
                                                        <tbody>
                                                            <?php foreach ($hidden_entries as $entry) : ?>
                                                                <tr>
                                                                    <td>
                                                                        <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['timestamp']))); ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($entry['success']) : ?>
                                                                            <span class="badge bg-success">
                                                                                <i class="fas fa-check-circle"></i>
                                                                                <?php esc_html_e('Success', 'debug-log-viewer'); ?>
                                                                            </span>
                                                                        <?php else : ?>
                                                                            <span class="badge bg-danger">
                                                                                <i class="fas fa-times-circle"></i>
                                                                                <?php esc_html_e('Failed', 'debug-log-viewer'); ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        $method_labels = array(
                                                                            'truncateKeepLast' => __('Truncate', 'debug-log-viewer'),
                                                                            'clear' => __('Clear', 'debug-log-viewer'),
                                                                            'archiveAndRotate' => __('Archive', 'debug-log-viewer'),
                                                                            'unknown' => __('Unknown', 'debug-log-viewer'),
                                                                        );
                                                                        echo esc_html($method_labels[$entry['method']] ?? $entry['method']);
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if (isset($entry['old_size']) && $entry['old_size'] > 0) {
                                                                            echo esc_html(size_format($entry['old_size'], 2));
                                                                        } else {
                                                                            echo '—';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if (isset($entry['new_size']) && $entry['new_size'] > 0) {
                                                                            echo esc_html(size_format($entry['new_size'], 2));
                                                                        } else {
                                                                            echo '—';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if (isset($entry['old_size']) && isset($entry['new_size']) && $entry['old_size'] > 0) {
                                                                            $reduction = $entry['old_size'] - $entry['new_size'];
                                                                            $reduction_percent = round(($reduction / $entry['old_size']) * 100, 1);
                                                                            echo '<span class="text-success">';
                                                                            echo esc_html(size_format($reduction, 2));
                                                                            echo ' (' . esc_html($reduction_percent) . '%)';
                                                                            echo '</span>';
                                                                        } else {
                                                                            echo '—';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php echo esc_html($entry['message']); ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </details>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            <?php
                            /* translators: %1$d: number of entries displayed, %2$d: total entries */
                            printf(esc_html__('Showing %1$d of %2$d cleanup entries (max 50 stored).', 'debug-log-viewer'), intval(min(10, $total_entries)), intval($total_entries)); ?>
                        </small>
                    <?php else : ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i>
                            <?php esc_html_e('No cleanup history yet. Run a manual cleanup or enable automatic cleanup to see entries here.', 'debug-log-viewer'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cleanup Confirmation Modal -->
    <div class="modal fade" id="cleanup-confirmation-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <p>
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <?php esc_html_e('Are you sure you want to clean up the log file now?', 'debug-log-viewer'); ?>
                        </p>
                        <p>
                            <?php esc_html_e('This action cannot be undone.', 'debug-log-viewer'); ?>
                        </p>
                        <p>
                            <?php esc_html_e('The cleanup will use your current settings:', 'debug-log-viewer'); ?>
                        </p>
                        <ul>
                            <li><?php
                                /* translators: %s: cleanup method name */
                                printf(esc_html__('Method: %s', 'debug-log-viewer'), '<strong>' . esc_html($stats['cleanup_method']) . '</strong>'); ?></li>
                            <?php if ($stats['cleanup_method'] === LogCleanupService::METHOD_TRUNCATE_KEEP_LAST) : ?>
                                <li><?php
                                    /* translators: %d: retention percentage */
                                    printf(esc_html__('Retention: Keep last %d%%', 'debug-log-viewer'), esc_html($stats['retention_percentage'])); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="cancel-cleanup">
                        <?php esc_html_e('Cancel', 'debug-log-viewer'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirm-cleanup">
                        <?php esc_html_e('Clean Up Now', 'debug-log-viewer'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cleanup-confirmation-modal').addEventListener('shown.bs.modal', function() {
            document.getElementById('confirm-cleanup').focus();
        });
    </script>
</div>