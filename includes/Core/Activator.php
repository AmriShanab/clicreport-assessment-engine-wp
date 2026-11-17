<?php
/**
 * Plugin Activation Handler
 *
 * @package ClicReport\AssessmentEngine\Core
 */

namespace ClicReport\AssessmentEngine\Core;

defined( 'ABSPATH' ) || exit;

final class Activator {

    /**
     * Executed by WordPress when the plugin is activated.
     *
     * @param bool $networkWide True if network-activated on Multisite.
     */
    public static function activate( bool $networkWide = false ): void {
        self::assertEnvironment();

        if ( is_multisite() && $networkWide ) {
            $siteIds = get_sites( [ 'fields' => 'ids' ] );
            foreach ( $siteIds as $siteId ) {
                switch_to_blog( (int) $siteId );
                self::activateSingleSite();
                restore_current_blog();
            }
            return;
        }

        self::activateSingleSite();
    }

    /**
     * Block activation if WordPress version is below minimum requirement.
     */
    private static function assertEnvironment(): void {
        global $wp_version;

        if ( version_compare( $wp_version, CRAE_MIN_WP_VERSION, '<' ) ) {
            deactivate_plugins( CRAE_PLUGIN_BASENAME );
            wp_die(
                esc_html(
                    sprintf(
                        'ClicReport Assessment Engine requires WordPress %s or higher. Current version: %s.',
                        CRAE_MIN_WP_VERSION,
                        $wp_version
                    )
                ),
                esc_html__( 'Plugin Activation Error', 'clicreport-assessment-engine' ),
                [ 'back_link' => true ]
            );
        }
    }

    /**
     * Run per-site activation tasks (options, protected log folder, migrations).
     */
    private static function activateSingleSite(): void {
        self::createDefaultOptions();
        self::protectLogDirectory();

        // Phase 2: Database\Migrator::migrate() & Seeders will execute here.
        \ClicReport\AssessmentEngine\Database\Migrator::migrate();
        \ClicReport\AssessmentEngine\Database\Seeders\TemplateCatalogSeeder::seed();
        \ClicReport\AssessmentEngine\Database\Seeders\ClinicalRulesSeeder::seed();

        flush_rewrite_rules();
    }

    /**
     * Seed default plugin options if they do not exist yet.
     */
    private static function createDefaultOptions(): void {
        $schema = require CRAE_PLUGIN_DIR . 'config/settings-schema.php';
        add_option( 'crae_settings', $schema['defaults'] ?? [] );
    }

    /**
     * Create a browser-blocked log directory inside wp-content/uploads/.
     */
    private static function protectLogDirectory(): void {
        $uploadDir = wp_upload_dir();
        $logDir    = trailingslashit( $uploadDir['basedir'] ) . 'crae-logs';

        if ( ! file_exists( $logDir ) ) {
            wp_mkdir_p( $logDir );
        }

        $htaccessFile = $logDir . '/.htaccess';
        if ( ! file_exists( $htaccessFile ) ) {
            file_put_contents( $htaccessFile, "Deny from all\n" );
        }

        $indexFile = $logDir . '/index.php';
        if ( ! file_exists( $indexFile ) ) {
            file_put_contents( $indexFile, "<?php\n// Silence is golden.\n" );
        }
    }
}