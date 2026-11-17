<?php
/**
 * Fired when the plugin is deleted from WordPress Admin -> Plugins.
 *
 * @package ClicReport\AssessmentEngine
 */
// 1. Security Gate: Must only run via WordPress uninstall routine.
defined('WP_UNINSTALL_PLUGIN') || exit;

$craeSettings = get_option('crae_settings', []);
if(empty($craeSettings['delete_data_on_uninstall'])) {
    return;
}

global $wpdb;

$craeTables = [
    'audit_logs',
    'decision_options',
    'validity_rules',
    'narrative_rules',
    'score_ranges',
    'scales',
    'sections',
    'templates',
];

foreach ( $craeTables as $table ) {
    $tableName = $wpdb->prefix . 'crae_' . $table;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS `{$tableName}`" );
}

delete_option('crae_settings');
delete_option('crae_db_version');