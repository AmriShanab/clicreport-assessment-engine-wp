<?php
/**
 * Plugin Deactivation Handler
 *
 * @package ClicReport\AssessmentEngine\Core
 */

namespace ClicReport\AssessmentEngine\Core;

defined( 'ABSPATH' ) || exit;

final class Deactivator {

    /**
     * Executed by WordPress when the plugin is deactivated.
     */
    public static function deactivate(): void {
        // Clear any scheduled background cron hooks added in later phases.
        wp_clear_scheduled_hook( 'crae_cleanup_expired_audit_logs' );

        // Reset WordPress rewrite rules cleanly.
        flush_rewrite_rules();
    }
}