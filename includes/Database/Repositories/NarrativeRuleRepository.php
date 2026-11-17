<?php
/**
 * Narrative Rules, Validity Rules & Decision Options Repository
 *
 * @package ClicReport\AssessmentEngine\Database\Repositories
 */

namespace ClicReport\AssessmentEngine\Database\Repositories;

defined( 'ABSPATH' ) || exit;

final class NarrativeRuleRepository extends AbstractRepository {

    /**
     * Fetch narrative rules for a batch of scale IDs indexed by [scale_id][trigger_condition].
     *
     * @param int[] $scaleIds
     */
    public function getRulesIndexedByScale( array $scaleIds ): array {
        $scaleIds = array_values( array_filter( array_map( 'intval', $scaleIds ) ) );
        if ( empty( $scaleIds ) ) {
            return [];
        }

        $table        = $this->table( 'narrative_rules' );
        $placeholders = implode( ',', array_fill( 0, count( $scaleIds ), '%d' ) );

        // Dynamic %d placeholder count prepared safely via $wpdb->prepare().
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE scale_id IN ({$placeholders})",
                $scaleIds
            ),
            ARRAY_A
        );

        $indexed = [];
        foreach ( (array) $rows as $row ) {
            $indexed[ (int) $row['scale_id'] ][ $row['trigger_condition'] ] = $row['narrative_template'];
        }

        return $indexed;
    }

    /**
     * Fetch validity rules (F-Index, Consistency, etc.) for a template.
     */
    public function getValidityRules( int $templateId ): array {
        $table = $this->table( 'validity_rules' );
        $rows  = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_id = %d",
                $templateId
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Fetch qualitative decision options (Archetype 5 matrices) for a template.
     */
    public function getDecisionOptions( int $templateId ): array {
        $table = $this->table( 'decision_options' );
        $rows  = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_id = %d ORDER BY sort_order ASC, id ASC",
                $templateId
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }
}