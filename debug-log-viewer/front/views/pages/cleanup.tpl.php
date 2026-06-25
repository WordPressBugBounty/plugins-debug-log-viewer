<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use DebugLogViewer\Admin\Services\LogCleanupService;
use DebugLogViewer\Admin\Controllers\CleanupController;
use function DebugLogViewer\Admin\Controllers\dbg_lv;

$cleanup_service    = new LogCleanupService();
$cleanup_controller = new CleanupController();

$stats         = $cleanup_service->get_cleanup_stats();
$schedule_info = $cleanup_controller->get_schedule_info();
$history       = $cleanup_controller->getCleanupHistory( 0 );

$is_premium = dbg_lv()->is_premium();

$pct             = isset( $stats['percentage_used'] ) ? (float) $stats['percentage_used'] : 0;
$bar_class       = $pct > 90 ? 'bg-danger' : ( $pct > 70 ? 'bg-warning' : 'bg-success' );
$quota_msg_class = $pct > 90 ? 'quota-danger' : ( $pct > 70 ? 'quota-warning' : 'quota-ok' );
$quota_msg       = $pct > 90
    ? __( 'Alert: log file is near the limit. Cleanup may trigger soon.', 'debug-log-viewer' )
    : ( $pct > 70
        ? __( 'Warning: log file is approaching the limit.', 'debug-log-viewer' )
        : __( 'Your debug.log file is within the allowed limit.', 'debug-log-viewer' ) );
?>

<div class="tab-pane fade <?php echo ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) === 'cleanup' ) ? 'show active' : ''; ?>"
    id="cleanup-content"
    role="tabpanel"
    aria-labelledby="cleanup-tab-btn">

    <div class="container">
        <div class="cleanup">

            <?php if ( isset( $stats['percentage_used'] ) ) : ?>
            <div class="cleanup-quota-card">
                <div class="cleanup-quota-header">
                    <div class="cleanup-quota-icon-wrap">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div class="cleanup-quota-info">
                        <span class="cleanup-quota-title"><?php esc_html_e( 'Quota Usage', 'debug-log-viewer' ); ?></span>
                        <span class="cleanup-quota-size">
                            <?php echo esc_html( number_format( $stats['current_size_mb'], 2 ) ); ?> / <?php echo esc_html( number_format( $stats['max_size_mb'], 0 ) ); ?> MB
                        </span>
                    </div>
                </div>
                <div class="cleanup-quota-body">
                    <div class="cleanup-progress">
                        <div class="cleanup-progress-bar <?php echo esc_attr( $bar_class ); ?>"
                            style="width: <?php echo esc_attr( min( $pct, 100 ) ); ?>%">
                            <?php echo esc_html( number_format( $pct, 1 ) ); ?>%
                        </div>
                    </div>
                    <p class="cleanup-quota-msg <?php echo esc_attr( $quota_msg_class ); ?>">
                        <?php echo esc_html( $quota_msg ); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $id      = 'next-cleanup-info';
            $dynamic = true;
            include plugin_dir_path( __DIR__ ) . 'components/next-schedule-info.tpl.php';
            ?>

            <div class="cleanup-layout">

                <!-- ======================================================= -->
                <!-- Settings Form                                            -->
                <!-- ======================================================= -->
                <form class="cleanup-card"
                    id="dbg_lv_cleanup_settings_form"
                    onsubmit="return false"
                    data-cleanup-enabled="<?php echo esc_attr( $stats['enabled'] ? 'true' : 'false' ); ?>">

                    <div class="cleanup-card-header">
                        <div class="cleanup-card-icon-wrap">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div class="cleanup-card-info">
                            <div class="cleanup-card-title"><?php esc_html_e( 'Cleanup Settings', 'debug-log-viewer' ); ?></div>
                            <div class="cleanup-card-desc"><?php esc_html_e( 'Configure automatic log file cleanup.', 'debug-log-viewer' ); ?></div>
                        </div>
                    </div>

                    <div class="cleanup-card-body">

                        <?php if ( ! $is_premium ) : ?>
                            <div class="pro-feature-notice">
                                <p class="upgrade-notice">
                                    <i class="fas fa-rocket"></i>
                                    <?php esc_html_e( 'Upgrade to Pro for custom size limits, flexible schedules, email notifications, and cleanup history tracking.', 'debug-log-viewer' ); ?>
                                    <span class="upgrade-link freemius-checkout-trigger"><?php esc_html_e( 'Upgrade Now', 'debug-log-viewer' ); ?></span>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Section: Trigger -->
                        <div class="settings-section">
                            <div class="settings-cards-grid">

                                <!-- Max Log Size -->
                                <div class="settings-card">
                                    <div class="settings-card-body">
                                        <div class="settings-card-info">
                                            <div class="settings-card-title"><?php esc_html_e( 'Maximum Log Size', 'debug-log-viewer' ); ?></div>
                                            <div class="settings-card-desc">
                                                <?php echo $is_premium
                                                    ? esc_html( sprintf( __( 'Cleanup triggers when log exceeds this size (%1$d–%2$d MB).', 'debug-log-viewer' ), intval( LogCleanupService::MIN_MAX_LOG_SIZE_MB ), intval( LogCleanupService::MAX_MAX_LOG_SIZE_MB ) ) )
                                                    : esc_html__( 'Free plan is fixed at 50 MB for optimal performance.', 'debug-log-viewer' ); ?>
                                            </div>
                                        </div>
                                        <div class="settings-card-control">
                                            <div class="cleanup-input-with-unit">
                                                <?php if ( $is_premium ) : ?>
                                                    <input type="number" class="cleanup-input"
                                                        id="dbg_lv_max_log_size" name="dbg_lv_max_log_size"
                                                        value="<?php echo esc_attr( $stats['max_size_mb'] ); ?>"
                                                        min="<?php echo esc_attr( LogCleanupService::MIN_MAX_LOG_SIZE_MB ); ?>"
                                                        max="<?php echo esc_attr( LogCleanupService::MAX_MAX_LOG_SIZE_MB ); ?>"
                                                        step="1">
                                                <?php else : ?>
                                                    <input type="text" class="cleanup-input" value="50" readonly>
                                                    <input type="hidden" id="dbg_lv_max_log_size" name="dbg_lv_max_log_size" value="50">
                                                <?php endif; ?>
                                                <span class="cleanup-input-unit">MB</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Check Frequency -->
                                <div class="settings-card">
                                    <div class="settings-card-body">
                                        <div class="settings-card-info">
                                            <div class="settings-card-title"><?php esc_html_e( 'Check Frequency', 'debug-log-viewer' ); ?></div>
                                            <div class="settings-card-desc">
                                                <?php echo $is_premium
                                                    ? esc_html__( 'How often the system checks if cleanup is needed.', 'debug-log-viewer' )
                                                    : esc_html__( 'Free plan checks daily to keep your log file under control.', 'debug-log-viewer' ); ?>
                                            </div>
                                        </div>
                                        <div class="settings-card-control">
                                            <?php if ( $is_premium ) : ?>
                                                <select class="cleanup-select" id="dlv_cleanup_schedule" name="dlv_cleanup_schedule">
                                                    <option value="hourly"     <?php selected( $schedule_info['schedule'], 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'debug-log-viewer' ); ?></option>
                                                    <option value="twicedaily" <?php selected( $schedule_info['schedule'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'debug-log-viewer' ); ?></option>
                                                    <option value="daily"      <?php selected( $schedule_info['schedule'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'debug-log-viewer' ); ?></option>
                                                    <option value="weekly"     <?php selected( $schedule_info['schedule'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'debug-log-viewer' ); ?></option>
                                                </select>
                                            <?php else : ?>
                                                <input type="text" class="cleanup-select" value="<?php esc_attr_e( 'Daily', 'debug-log-viewer' ); ?>" readonly>
                                                <input type="hidden" id="dlv_cleanup_schedule" name="dlv_cleanup_schedule" value="daily">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div><!-- /.settings-section -->

                        <!-- Section: Behavior -->
                        <div class="settings-section">
                            <div class="settings-cards-grid">

                                <!-- Cleanup Method -->
                                <div class="settings-card">
                                    <div class="settings-card-body">
                                        <div class="settings-card-info">
                                            <div class="settings-card-title"><?php esc_html_e( 'Cleanup Method', 'debug-log-viewer' ); ?></div>
                                            <div class="settings-card-desc">
                                                <?php echo $is_premium
                                                    ? esc_html__( 'Truncate keeps recent entries; Clear removes everything.', 'debug-log-viewer' )
                                                    : esc_html__( 'Truncate keeps the most recent log entries while reducing file size.', 'debug-log-viewer' ); ?>
                                            </div>
                                        </div>
                                        <div class="settings-card-control">
                                            <?php if ( $is_premium ) : ?>
                                                <select class="cleanup-select" id="dlv_cleanup_method" name="dlv_cleanup_method"
                                                    data-truncate-method="<?php echo esc_attr( LogCleanupService::METHOD_TRUNCATE_KEEP_LAST ); ?>">
                                                    <option value="<?php echo esc_attr( LogCleanupService::METHOD_TRUNCATE_KEEP_LAST ); ?>" <?php selected( $stats['cleanup_method'], LogCleanupService::METHOD_TRUNCATE_KEEP_LAST ); ?>>
                                                        <?php esc_html_e( 'Truncate (Keep Last X%)', 'debug-log-viewer' ); ?>
                                                    </option>
                                                    <option value="<?php echo esc_attr( LogCleanupService::METHOD_CLEAR_FILE ); ?>" <?php selected( $stats['cleanup_method'], LogCleanupService::METHOD_CLEAR_FILE ); ?>>
                                                        <?php esc_html_e( 'Clear File Completely', 'debug-log-viewer' ); ?>
                                                    </option>
                                                </select>
                                            <?php else : ?>
                                                <input type="text" class="cleanup-select" value="<?php esc_attr_e( 'Truncate', 'debug-log-viewer' ); ?>" readonly>
                                                <input type="hidden" id="dlv_cleanup_method" name="dlv_cleanup_method"
                                                    value="<?php echo esc_attr( LogCleanupService::METHOD_TRUNCATE_KEEP_LAST ); ?>"
                                                    data-truncate-method="<?php echo esc_attr( LogCleanupService::METHOD_TRUNCATE_KEEP_LAST ); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Retention Percentage -->
                                <?php if ( $is_premium ) : ?>
                                    <div class="settings-card" id="dlv_retention_row">
                                        <div class="settings-card-body">
                                            <div class="settings-card-info">
                                                <div class="settings-card-title"><?php esc_html_e( 'Retention Percentage', 'debug-log-viewer' ); ?></div>
                                                <div class="settings-card-desc">
                                                    <?php
                                                    printf(
                                                        /* translators: %1$d: min retention, %2$d: max retention */
                                                        esc_html__( 'Amount of the log kept when truncating (%1$d–%2$d%%). Truncate method only.', 'debug-log-viewer' ),
                                                        intval( LogCleanupService::MIN_RETENTION_PERCENT ),
                                                        intval( LogCleanupService::MAX_RETENTION_PERCENT )
                                                    );
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="settings-card-control">
                                                <div class="cleanup-input-with-unit">
                                                    <input type="number" class="cleanup-input"
                                                        id="dlv_retention_percentage" name="dlv_retention_percentage"
                                                        value="<?php echo esc_attr( $stats['retention_percentage'] ); ?>"
                                                        min="<?php echo esc_attr( LogCleanupService::MIN_RETENTION_PERCENT ); ?>"
                                                        max="<?php echo esc_attr( LogCleanupService::MAX_RETENTION_PERCENT ); ?>"
                                                        step="1">
                                                    <span class="cleanup-input-unit">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <input type="hidden" id="dlv_retention_percentage" name="dlv_retention_percentage" value="10">
                                    <div id="dlv_retention_row" style="display:none;"></div>
                                <?php endif; ?>

                            </div>
                        </div><!-- /.settings-section -->

                        <!-- Section: Notifications (Pro only) -->
                        <?php if ( $is_premium ) : ?>
                            <div class="settings-section">
                                <div class="settings-cards-grid settings-cards-grid--full">
                                    <div class="settings-card">
                                        <div class="settings-card-body">
                                            <div class="settings-card-info">
                                                <div class="settings-card-title"><?php esc_html_e( 'Email Notifications', 'debug-log-viewer' ); ?></div>
                                                <div class="settings-card-desc">
                                                    <?php
                                                    printf(
                                                        /* translators: %s: admin email address */
                                                        esc_html__( 'Send an email after each cleanup. Notifications will be sent to: %s', 'debug-log-viewer' ),
                                                        '<strong>' . esc_html( get_option( 'admin_email' ) ) . '</strong>'
                                                    );
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="settings-card-control">
                                                <label class="toggle-switch" for="dlv_email_notifications">
                                                    <input type="checkbox"
                                                        id="dlv_email_notifications"
                                                        name="dlv_email_notifications"
                                                        value="1"
                                                        <?php checked( $stats['email_notifications'], true ); ?>>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else : ?>
                            <input type="hidden" id="dlv_email_notifications" name="dlv_email_notifications" value="0">
                        <?php endif; ?>

                    </div><!-- /.cleanup-card-body -->

                    <div class="cleanup-card-footer">
                        <input type="submit"
                            class="btn btn-primary btn-sm"
                            id="dbg_lv_save_cleanup_settings"
                            value="<?php esc_attr_e( 'Loading...', 'debug-log-viewer' ); ?>"
                            data-nonce="<?php echo esc_attr( wp_create_nonce( 'dbg_lv_save_cleanup_settings' ) ); ?>"
                            disabled />
                        <?php if ( $stats['enabled'] ) : ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="dbg_lv_run_cleanup_now">
                            <i class="fa-solid fa-play"></i>
                            <?php esc_html_e( 'Run Manually', 'debug-log-viewer' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                </form><!-- /.cleanup-card (settings) -->

                <!-- ======================================================= -->
                <!-- History Card                                             -->
                <!-- ======================================================= -->
                <?php if ( $is_premium ) : ?>

                    <div class="cleanup-card cleanup-history-card">

                        <div class="cleanup-card-header">
                            <div class="cleanup-card-icon-wrap">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div class="cleanup-card-info">
                                <div class="cleanup-card-title"><?php esc_html_e( 'Cleanup History', 'debug-log-viewer' ); ?></div>
                                <div class="cleanup-card-desc"><?php esc_html_e( 'Recent automatic and manual cleanup events.', 'debug-log-viewer' ); ?></div>
                            </div>
                        </div>

                        <div class="cleanup-card-body cleanup-history-body">
                            <?php if ( ! empty( $history ) ) : ?>
                                <div class="table-responsive">
                                    <table class="cleanup-history-table" id="cleanup-history-table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e( 'Date & Time', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'Status', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'Method', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'Before', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'After', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'Reduction', 'debug-log-viewer' ); ?></th>
                                                <th><?php esc_html_e( 'Message', 'debug-log-viewer' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $history as $entry ) : ?>
                                                <?php
                                                $ts        = ( new \DateTime( $entry['timestamp'], new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
                                                $date      = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );
                                                $reduction = ( isset( $entry['old_size'], $entry['new_size'] ) && $entry['old_size'] > 0 )
                                                    ? $entry['old_size'] - $entry['new_size'] : 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo esc_html( $date ); ?></td>
                                                    <td>
                                                        <?php if ( ! empty( $entry['skipped'] ) ) : ?>
                                                            <span class="cleanup-badge cleanup-badge-skipped">
                                                                <i class="fa-solid fa-circle-minus"></i>
                                                                <?php esc_html_e( 'Skipped', 'debug-log-viewer' ); ?>
                                                            </span>
                                                        <?php elseif ( $entry['success'] ) : ?>
                                                            <span class="cleanup-badge cleanup-badge-success">
                                                                <i class="fa-solid fa-circle-check"></i>
                                                                <?php esc_html_e( 'Success', 'debug-log-viewer' ); ?>
                                                            </span>
                                                        <?php else : ?>
                                                            <span class="cleanup-badge cleanup-badge-failed">
                                                                <i class="fa-solid fa-circle-xmark"></i>
                                                                <?php esc_html_e( 'Failed', 'debug-log-viewer' ); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo esc_html( LogCleanupService::get_method_label( $entry['method'] ) ); ?></td>
                                                    <td><?php echo isset( $entry['old_size'] ) && $entry['old_size'] > 0 ? esc_html( size_format( $entry['old_size'], 2 ) ) : '—'; ?></td>
                                                    <td><?php echo isset( $entry['new_size'] ) && $entry['new_size'] > 0 ? esc_html( size_format( $entry['new_size'], 2 ) ) : '—'; ?></td>
                                                    <td>
                                                        <?php if ( $reduction > 0 ) : ?>
                                                            <span class="cleanup-reduction">−<?php echo esc_html( size_format( $reduction, 2 ) ); ?></span>
                                                        <?php else : ?>
                                                            —
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo esc_html( $entry['message'] ); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else : ?>
                                <div class="cleanup-history-empty">
                                    <i class="fa-regular fa-clock"></i>
                                    <p><?php esc_html_e( 'No cleanup history yet. Run a manual cleanup or enable automatic cleanup to see entries here.', 'debug-log-viewer' ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /.cleanup-history-card -->

                <?php else : ?>

                    <div class="cleanup-card cleanup-upsell-card">
                        <div class="cleanup-card-header">
                            <div class="cleanup-card-icon-wrap">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div class="cleanup-card-info">
                                <div class="cleanup-card-title"><?php esc_html_e( 'Cleanup History', 'debug-log-viewer' ); ?></div>
                                <div class="cleanup-card-desc"><?php esc_html_e( 'Track every cleanup event with timestamps and file size details.', 'debug-log-viewer' ); ?></div>
                            </div>
                        </div>
                        <div class="cleanup-card-body">
                            <div class="pro-feature-notice">
                                <p class="upgrade-notice">
                                    <i class="fas fa-rocket"></i>
                                    <?php esc_html_e( 'Upgrade to Pro to unlock cleanup history tracking.', 'debug-log-viewer' ); ?>
                                    <span class="upgrade-link freemius-checkout-trigger"><?php esc_html_e( 'Upgrade Now', 'debug-log-viewer' ); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            </div><!-- /.cleanup-layout -->

        </div><!-- /.cleanup -->
    </div><!-- /.container -->

    <!-- Confirmation modal -->
    <div class="modal fade" id="cleanup-confirmation-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <p><i class="fa fa-exclamation-triangle me-2"></i><?php esc_html_e( 'Are you sure you want to clean up the log file now?', 'debug-log-viewer' ); ?></p>
                        <p><?php esc_html_e( 'This action cannot be undone.', 'debug-log-viewer' ); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="cancel-cleanup">
                        <?php esc_html_e( 'Cancel', 'debug-log-viewer' ); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirm-cleanup">
                        <?php esc_html_e( 'Run Now', 'debug-log-viewer' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.tab-pane -->