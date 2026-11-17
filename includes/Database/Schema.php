<?php
/**
 * WordPress MySQL Schema Definitions (dbDelta-compatible)
 *
 * @package ClicReport\AssessmentEngine\Database
 */

namespace ClicReport\AssessmentEngine\Database;

defined( 'ABSPATH' ) || exit;

final class Schema {

    /**
     * Build the full table name using WordPress's dynamic $wpdb->prefix.
     */
    public static function tableName( \wpdb $wpdb, string $shortName ): string {
        return $wpdb->prefix . CRAE_TABLE_PREFIX . $shortName;
    }

    /**
     * Return dbDelta-ready CREATE TABLE statements for all 8 plugin tables.
     *
     * @return array<string, string> Map of short table name => SQL DDL.
     */
    public static function getTablesSql( \wpdb $wpdb ): array {
        $charset = $wpdb->get_charset_collate();

        $templates       = self::tableName( $wpdb, 'templates' );
        $sections        = self::tableName( $wpdb, 'sections' );
        $scales          = self::tableName( $wpdb, 'scales' );
        $scoreRanges     = self::tableName( $wpdb, 'score_ranges' );
        $narrativeRules  = self::tableName( $wpdb, 'narrative_rules' );
        $validityRules   = self::tableName( $wpdb, 'validity_rules' );
        $decisionOptions = self::tableName( $wpdb, 'decision_options' );
        $auditLogs       = self::tableName( $wpdb, 'audit_logs' );

        return [
            'templates' => "CREATE TABLE {$templates} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_slug varchar(120) NOT NULL,
                template_name varchar(255) NOT NULL,
                publisher varchar(100) DEFAULT NULL,
                category varchar(100) NOT NULL DEFAULT 'Social-Emotional',
                archetype varchar(50) NOT NULL DEFAULT 't_score',
                default_score_system varchar(40) NOT NULL DEFAULT 't_score',
                intro_narrative longtext DEFAULT NULL,
                config_json longtext DEFAULT NULL,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY uq_template_slug (template_slug),
                KEY idx_archetype (archetype),
                KEY idx_category (category)
            ) {$charset};",

            'sections' => "CREATE TABLE {$sections} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned NOT NULL,
                section_key varchar(100) NOT NULL,
                title varchar(255) NOT NULL,
                intro_narrative longtext DEFAULT NULL,
                toggle_key varchar(100) DEFAULT NULL,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY uq_template_section (template_id, section_key),
                KEY idx_template_section_order (template_id, sort_order)
            ) {$charset};",

            'scales' => "CREATE TABLE {$scales} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned NOT NULL,
                section_id bigint(20) unsigned DEFAULT NULL,
                parent_scale_id bigint(20) unsigned DEFAULT NULL,
                scale_key varchar(100) NOT NULL,
                title varchar(255) NOT NULL,
                scale_type varchar(40) NOT NULL DEFAULT 'clinical',
                is_composite tinyint(1) NOT NULL DEFAULT 0,
                cutoff_threshold decimal(6,2) DEFAULT NULL,
                description longtext NOT NULL,
                rater_restriction varchar(30) NOT NULL DEFAULT 'all',
                age_restriction varchar(30) NOT NULL DEFAULT 'all',
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY uq_template_scale (template_id, scale_key),
                KEY idx_template_scales (template_id, sort_order),
                KEY idx_parent_scale (parent_scale_id)
            ) {$charset};",

            'score_ranges' => "CREATE TABLE {$scoreRanges} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned DEFAULT NULL,
                scale_type varchar(40) NOT NULL,
                min_score decimal(6,2) NOT NULL,
                max_score decimal(6,2) NOT NULL,
                range_label varchar(100) NOT NULL,
                star_symbol varchar(10) NOT NULL DEFAULT '',
                color_hex varchar(20) NOT NULL DEFAULT '',
                is_elevated tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY idx_range_lookup (template_id, scale_type, min_score, max_score)
            ) {$charset};",

            'narrative_rules' => "CREATE TABLE {$narrativeRules} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                scale_id bigint(20) unsigned NOT NULL,
                trigger_condition varchar(60) NOT NULL,
                narrative_template longtext NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_scale_condition (scale_id, trigger_condition)
            ) {$charset};",

            'validity_rules' => "CREATE TABLE {$validityRules} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned NOT NULL,
                scale_name varchar(100) NOT NULL,
                caution_level varchar(30) NOT NULL DEFAULT 'acceptable',
                diagnostic_description longtext DEFAULT NULL,
                narrative_template longtext NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_template_validity (template_id, scale_name, caution_level)
            ) {$charset};",

            'decision_options' => "CREATE TABLE {$decisionOptions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                template_id bigint(20) unsigned NOT NULL,
                criterion_key varchar(100) NOT NULL,
                option_value varchar(100) NOT NULL,
                narrative_fragment longtext NOT NULL,
                sort_order int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY idx_template_criterion (template_id, criterion_key, option_value)
            ) {$charset};",

            'audit_logs' => "CREATE TABLE {$auditLogs} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                request_id varchar(64) NOT NULL,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                template_slug varchar(120) NOT NULL,
                archetype varchar(50) NOT NULL,
                execution_time_ms decimal(8,2) NOT NULL DEFAULT 0.00,
                status varchar(40) NOT NULL DEFAULT 'success',
                ip_hash char(64) NOT NULL DEFAULT '',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_req_id (request_id),
                KEY idx_template_log (template_slug, created_at),
                KEY idx_user_log (user_id, created_at)
            ) {$charset};",
        ];
    }
}