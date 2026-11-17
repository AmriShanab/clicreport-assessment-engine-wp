<?php
/**
 * Versioned Database Migration Runner
 *
 * @package ClicReport\AssessmentEngine\Database
 */

namespace ClicReport\AssessmentEngine\Database;

defined( 'ABSPATH' ) || exit;

final class Migrator {

    /**
     * Execute pending database schema migrations via WordPress dbDelta().
     */
    public static function migrate(): void {
        global $wpdb;

        $installedVersion = (string) get_option( 'crae_db_version', '' );

        if ( version_compare( $installedVersion, CRAE_DB_VERSION, '>=' ) && self::tablesExist( $wpdb ) ) {
            return;
        }

        // dbDelta() lives inside wp-admin/includes/upgrade.php (not loaded on normal requests).
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        foreach ( Schema::getTablesSql( $wpdb ) as $sql ) {
            dbDelta( $sql );
        }

        update_option( 'crae_db_version', CRAE_DB_VERSION );
    }

    /**
     * Sanity check verifying all plugin tables exist in MySQL.
     */
    public static function tablesExist( \wpdb $wpdb ): bool {
        $templatesTable = Schema::tableName( $wpdb, 'templates' );
        $found          = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $templatesTable ) );

        return $found === $templatesTable;
    }
}