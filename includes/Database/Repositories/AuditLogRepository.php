<?php
/**
 * Non-PII Execution Audit Log Repository (HIPAA/FERPA Safe)
 *
 * @package ClicReport\AssessmentEngine\Database\Repositories
 */

namespace ClicReport\AssessmentEngine\Database\Repositories;

defined( 'ABSPATH' ) || exit;

final class AuditLogRepository extends AbstractRepository {

    /**
     * Record a report generation event without storing student names or scores.
     */
    public function record(
        string $requestId,
        int $userId,
        string $templateSlug,
        string $archetype,
        float $executionMs,
        string $status = 'success',
        string $rawIp = ''
    ): int {
        $table  = $this->table( 'audit_logs' );
        $ipHash = '' !== $rawIp ? hash_hmac( 'sha256', $rawIp, wp_salt( 'auth' ) ) : '';

        $this->wpdb->insert(
            $table,
            [
                'request_id'        => sanitize_text_field( $requestId ),
                'user_id'           => $userId,
                'template_slug'     => sanitize_key( $templateSlug ),
                'archetype'         => sanitize_key( $archetype ),
                'execution_time_ms' => round( $executionMs, 2 ),
                'status'            => sanitize_key( $status ),
                'ip_hash'           => $ipHash,
            ],
            [ '%s', '%d', '%s', '%s', '%f', '%s', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Return paginated audit log records for the WP Admin log viewer.
     */
    public function paginate( int $page = 1, int $perPage = 25 ): array {
        $table   = $this->table( 'audit_logs' );
        $page    = max( 1, $page );
        $perPage = max( 1, min( 100, $perPage ) );
        $offset  = ( $page - 1 ) * $perPage;

        $total = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            ),
            ARRAY_A
        );

        return [
            'items'       => is_array( $items ) ? $items : [],
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil( $total / $perPage ),
        ];
    }
}