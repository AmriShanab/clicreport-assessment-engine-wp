<?php
/**
 * Assessment Scales & Score Ranges Repository
 *
 * @package ClicReport\AssessmentEngine\Database\Repositories
 */

namespace ClicReport\AssessmentEngine\Database\Repositories;

defined( 'ABSPATH' ) || exit;

final class ScaleRepository extends AbstractRepository {

    /**
     * Fetch all scales for a template ordered by sort_order.
     */
    public function getByTemplate( int $templateId ): array {
        $table = $this->table( 'scales' );
        $rows  = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_id = %d ORDER BY sort_order ASC, id ASC",
                $templateId
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Fetch template-specific score ranges, falling back to universal rules (template_id IS NULL).
     */
    public function getScoreRanges( ?int $templateId, string $scaleType ): array {
        $table = $this->table( 'score_ranges' );

        if ( null !== $templateId ) {
            $specific = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} WHERE template_id = %d AND scale_type = %s ORDER BY min_score ASC",
                    $templateId,
                    $scaleType
                ),
                ARRAY_A
            );

            if ( ! empty( $specific ) ) {
                return $specific;
            }
        }

        $universal = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_id IS NULL AND scale_type = %s ORDER BY min_score ASC",
                $scaleType
            ),
            ARRAY_A
        );

        return is_array( $universal ) ? $universal : [];
    }

    /**
     * Insert or update a scale definition and return its primary ID.
     */
    public function upsert( array $data ): int {
        $table = $this->table( 'scales' );
        $sql   = $this->wpdb->prepare(
            "INSERT INTO {$table}
                (template_id, section_id, parent_scale_id, scale_key, title, scale_type, is_composite, cutoff_threshold, description, rater_restriction, age_restriction, sort_order)
             VALUES (%d, NULLIF(%d, 0), NULLIF(%d, 0), %s, %s, %s, %d, %s, %s, %s, %s, %d)
             ON DUPLICATE KEY UPDATE
                section_id = VALUES(section_id),
                parent_scale_id = VALUES(parent_scale_id),
                title = VALUES(title),
                scale_type = VALUES(scale_type),
                is_composite = VALUES(is_composite),
                cutoff_threshold = VALUES(cutoff_threshold),
                description = VALUES(description),
                rater_restriction = VALUES(rater_restriction),
                age_restriction = VALUES(age_restriction),
                sort_order = VALUES(sort_order)",
            (int) $data['template_id'],
            (int) ( $data['section_id'] ?? 0 ),
            (int) ( $data['parent_scale_id'] ?? 0 ),
            (string) $data['scale_key'],
            (string) $data['title'],
            (string) ( $data['scale_type'] ?? 'clinical' ),
            (int) ( $data['is_composite'] ?? 0 ),
            isset( $data['cutoff_threshold'] ) ? (string) $data['cutoff_threshold'] : null,
            (string) ( $data['description'] ?? '' ),
            (string) ( $data['rater_restriction'] ?? 'all' ),
            (string) ( $data['age_restriction'] ?? 'all' ),
            (int) ( $data['sort_order'] ?? 0 )
        );

        $this->wpdb->query( $sql );

        $id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$table} WHERE template_id = %d AND scale_key = %s LIMIT 1",
                (int) $data['template_id'],
                (string) $data['scale_key']
            )
        );

        return (int) $id;
    }
}