<?php
/**
 * Non-PII HIPAA/FERPA Audit Logs Admin View
 *
 * @var array<string, mixed> $logData
 */

defined( 'ABSPATH' ) || exit;

$items      = is_array( $logData['items'] ?? null ) ? $logData['items'] : [];
$totalPages = (int) ( $logData['total_pages'] ?? 1 );
$page       = (int) ( $logData['page'] ?? 1 );
?>
<div class="wrap crae-admin-wrap">
    <h1><?php esc_html_e( 'Execution Audit Logs (HIPAA / FERPA Non-PII)', 'clicreport-assessment-engine' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Student names, clinical scores, and raw IP addresses are never stored. IP addresses are stored strictly as one-way salted HMAC hashes.', 'clicreport-assessment-engine' ); ?>
    </p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Request ID', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'WP User ID', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'Template Slug', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'Archetype', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'Execution (ms)', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'Status', 'clicreport-assessment-engine' ); ?></th>
                <th><?php esc_html_e( 'Timestamp (UTC)', 'clicreport-assessment-engine' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $items ) ) : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'No report executions logged yet.', 'clicreport-assessment-engine' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $items as $row ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( (string) ( $row['request_id'] ?? '' ) ); ?></code></td>
                        <td><?php echo esc_html( (string) ( $row['user_id'] ?? 0 ) ); ?></td>
                        <td><code><?php echo esc_html( (string) ( $row['template_slug'] ?? '' ) ); ?></code></td>
                        <td><?php echo esc_html( (string) ( $row['archetype'] ?? '' ) ); ?></td>
                        <td><?php echo esc_html( (string) ( $row['execution_time_ms'] ?? '0.00' ) ); ?> ms</td>
                        <td><span class="crae-badge"><?php echo esc_html( (string) ( $row['status'] ?? 'success' ) ); ?></span></td>
                        <td><?php echo esc_html( (string) ( $row['created_at'] ?? '' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $totalPages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(
                    (string) paginate_links(
                        [
                            'base'    => add_query_arg( 'paged', '%#%' ),
                            'format'  => '',
                            'current' => $page,
                            'total'   => $totalPages,
                        ]
                    )
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

