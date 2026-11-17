<?php
/**
 * Seeds Universal Score Ranges, Sections, Scales, Narrative Rules,
 * Validity Rules, and Archetype 5 Decision Options.
 *
 * @package ClicReport\AssessmentEngine\Database\Seeders
 */

namespace ClicReport\AssessmentEngine\Database\Seeders;

use ClicReport\AssessmentEngine\Database\Schema;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;

defined( 'ABSPATH' ) || exit;

final class ClinicalRulesSeeder {

    /**
     * Seed bundled clinical rules idempotently inside a transaction.
     */
    public static function seed(): void {
        global $wpdb;

        $file = CRAE_PLUGIN_DIR . 'config/seed-data/clinical-rules.json';
        if ( ! is_readable( $file ) ) {
            return;
        }

        $data = wp_json_file_decode( $file, [ 'associative' => true ] );
        if ( ! is_array( $data ) ) {
            return;
        }

        $scaleRepo = new ScaleRepository( $wpdb );
        $tplIds    = self::loadTemplateIdMap( $wpdb );

        $scaleRepo->beginTransaction();
        try {
            self::seedScoreRanges( $wpdb, $data['score_ranges'] ?? [], $tplIds );
            $secIds   = self::seedSections( $wpdb, $data['sections'] ?? [], $tplIds );
            $scaleIds = self::seedScales( $scaleRepo, $data['scales'] ?? [], $tplIds, $secIds );
            self::seedNarrativeRules( $wpdb, $data['narrative_rules'] ?? [], $tplIds, $scaleIds );
            self::seedValidityRules( $wpdb, $data['validity_rules'] ?? [], $tplIds );
            self::seedDecisionOptions( $wpdb, $data['decision_options'] ?? [], $tplIds );

            $scaleRepo->commit();
        } catch ( \Throwable $e ) {
            $scaleRepo->rollback();
            throw $e;
        }
    }

    private static function loadTemplateIdMap( \wpdb $wpdb ): array {
        $table = Schema::tableName( $wpdb, 'templates' );
        $rows  = $wpdb->get_results( "SELECT id, template_slug FROM {$table}", ARRAY_A );

        $map = [];
        foreach ( (array) $rows as $row ) {
            $map[ $row['template_slug'] ] = (int) $row['id'];
        }
        return $map;
    }

    private static function seedScoreRanges( \wpdb $wpdb, array $ranges, array $tplIds ): void {
        $table    = Schema::tableName( $wpdb, 'score_ranges' );
        $existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $existing > 0 ) {
            return; // Already seeded.
        }

        foreach ( $ranges as $r ) {
            $tplId = ! empty( $r['template_slug'] ) ? ( $tplIds[ $r['template_slug'] ] ?? 0 ) : 0;
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$table} (template_id, scale_type, min_score, max_score, range_label, star_symbol, color_hex, is_elevated)
                     VALUES (NULLIF(%d, 0), %s, %f, %f, %s, %s, %s, %d)",
                    $tplId,
                    (string) $r['scale_type'],
                    (float) $r['min_score'],
                    (float) $r['max_score'],
                    (string) $r['range_label'],
                    (string) ( $r['star_symbol'] ?? '' ),
                    (string) ( $r['color_hex'] ?? '' ),
                    (int) ( $r['is_elevated'] ?? 0 )
                )
            );
        }
    }

    private static function seedSections( \wpdb $wpdb, array $sections, array $tplIds ): array {
        $table = Schema::tableName( $wpdb, 'sections' );
        $map   = [];

        foreach ( $sections as $s ) {
            $tplId = $tplIds[ $s['template_slug'] ] ?? 0;
            if ( 0 === $tplId ) {
                continue;
            }

            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$table} (template_id, section_key, title, intro_narrative, toggle_key, sort_order)
                     VALUES (%d, %s, %s, %s, %s, %d)
                     ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        intro_narrative = VALUES(intro_narrative),
                        toggle_key = VALUES(toggle_key),
                        sort_order = VALUES(sort_order)",
                    $tplId,
                    (string) $s['section_key'],
                    (string) $s['title'],
                    $s['intro_narrative'] ?? null,
                    $s['toggle_key'] ?? null,
                    (int) ( $s['sort_order'] ?? 0 )
                )
            );

            $secId = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE template_id = %d AND section_key = %s LIMIT 1",
                    $tplId,
                    (string) $s['section_key']
                )
            );

            $map[ $tplId ][ $s['section_key'] ] = $secId;
        }

        return $map;
    }

    private static function seedScales( ScaleRepository $repo, array $scales, array $tplIds, array $secIds ): array {
        $scaleIds = [];

        // Pass 1: Insert all scales and resolve section_id.
        foreach ( $scales as $sc ) {
            $tplId = $tplIds[ $sc['template_slug'] ] ?? 0;
            if ( 0 === $tplId ) {
                continue;
            }

            $secId = ! empty( $sc['section_key'] ) ? ( $secIds[ $tplId ][ $sc['section_key'] ] ?? 0 ) : 0;
            $id    = $repo->upsert( [
                'template_id'       => $tplId,
                'section_id'        => $secId,
                'parent_scale_id'   => 0,
                'scale_key'         => $sc['scale_key'],
                'title'             => $sc['title'],
                'scale_type'        => $sc['scale_type'],
                'is_composite'      => $sc['is_composite'],
                'cutoff_threshold'  => $sc['cutoff_threshold'],
                'description'       => $sc['description'],
                'rater_restriction' => $sc['rater_restriction'],
                'age_restriction'   => $sc['age_restriction'],
                'sort_order'        => $sc['sort_order'],
            ] );

            $scaleIds[ $tplId ][ $sc['scale_key'] ] = $id;
        }

        // Pass 2: Link child subtests to their parent composite scale_id.
        foreach ( $scales as $sc ) {
            if ( empty( $sc['parent_scale_key'] ) ) {
                continue;
            }

            $tplId    = $tplIds[ $sc['template_slug'] ] ?? 0;
            $parentId = $scaleIds[ $tplId ][ $sc['parent_scale_key'] ] ?? 0;
            if ( $tplId > 0 && $parentId > 0 ) {
                $secId = ! empty( $sc['section_key'] ) ? ( $secIds[ $tplId ][ $sc['section_key'] ] ?? 0 ) : 0;
                $repo->upsert( [
                    'template_id'       => $tplId,
                    'section_id'        => $secId,
                    'parent_scale_id'   => $parentId,
                    'scale_key'         => $sc['scale_key'],
                    'title'             => $sc['title'],
                    'scale_type'        => $sc['scale_type'],
                    'is_composite'      => $sc['is_composite'],
                    'cutoff_threshold'  => $sc['cutoff_threshold'],
                    'description'       => $sc['description'],
                    'rater_restriction' => $sc['rater_restriction'],
                    'age_restriction'   => $sc['age_restriction'],
                    'sort_order'        => $sc['sort_order'],
                ] );
            }
        }

        return $scaleIds;
    }

    private static function seedNarrativeRules( \wpdb $wpdb, array $rules, array $tplIds, array $scaleIds ): void {
        $table = Schema::tableName( $wpdb, 'narrative_rules' );

        foreach ( $rules as $rule ) {
            $tplId   = $tplIds[ $rule['template_slug'] ] ?? 0;
            $scaleId = $scaleIds[ $tplId ][ $rule['scale_key'] ] ?? 0;
            if ( 0 === $scaleId ) {
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE scale_id = %d AND trigger_condition = %s LIMIT 1",
                    $scaleId,
                    (string) $rule['trigger_condition']
                )
            );

            if ( $exists ) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'scale_id'           => $scaleId,
                    'trigger_condition'  => (string) $rule['trigger_condition'],
                    'narrative_template' => (string) $rule['narrative_template'],
                ],
                [ '%d', '%s', '%s' ]
            );
        }
    }

    private static function seedValidityRules( \wpdb $wpdb, array $rules, array $tplIds ): void {
        $table = Schema::tableName( $wpdb, 'validity_rules' );

        foreach ( $rules as $rule ) {
            $tplId = $tplIds[ $rule['template_slug'] ] ?? 0;
            if ( 0 === $tplId ) {
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE template_id = %d AND scale_name = %s AND caution_level = %s LIMIT 1",
                    $tplId,
                    (string) $rule['scale_name'],
                    (string) $rule['caution_level']
                )
            );

            if ( $exists ) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'template_id'            => $tplId,
                    'scale_name'             => (string) $rule['scale_name'],
                    'caution_level'          => (string) $rule['caution_level'],
                    'diagnostic_description' => $rule['diagnostic_description'] ?? null,
                    'narrative_template'     => (string) $rule['narrative_template'],
                ],
                [ '%d', '%s', '%s', '%s', '%s' ]
            );
        }
    }

    private static function seedDecisionOptions( \wpdb $wpdb, array $options, array $tplIds ): void {
        $table = Schema::tableName( $wpdb, 'decision_options' );

        foreach ( $options as $opt ) {
            $tplId = $tplIds[ $opt['template_slug'] ] ?? 0;
            if ( 0 === $tplId ) {
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE template_id = %d AND criterion_key = %s AND option_value = %s LIMIT 1",
                    $tplId,
                    (string) $opt['criterion_key'],
                    (string) $opt['option_value']
                )
            );

            if ( $exists ) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'template_id'        => $tplId,
                    'criterion_key'      => (string) $opt['criterion_key'],
                    'option_value'       => (string) $opt['option_value'],
                    'narrative_fragment' => (string) $opt['narrative_fragment'],
                    'sort_order'         => (int) ( $opt['sort_order'] ?? 0 ),
                ],
                [ '%d', '%s', '%s', '%s', '%d' ]
            );
        }
    }
}