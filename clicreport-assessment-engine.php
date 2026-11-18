<?php
/**
 * Plugin Name:       ClicReport Assessment & Clinical Narrative Engine
 * Plugin URI:        https://clicreport.com/plugins/clicreport-assessment-engine
 * Description:       Enterprise psychoeducational assessment scoring, multi-rater clinical narrative generation across 201 templates (5 psychometric archetypes), and Psivey AI integration for WordPress.
 * Version:           1.0.4
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            ClicReport Clinical Engineering Team
 * Author URI:        https://clicreport.com
 * Text Domain:       clicreport-assessment-engine
 * Domain Path:       /resources/languages
 *
 * @package           ClicReport\AssessmentEngine
 */

// 1. Security Gate: Block direct file execution.
defined('ABSPATH') || exit;

// 2. Define main plugin file path constant first (used by config/constants.php).
if (! defined('CRAE_PLUGIN_FILE')) {
    define('CRAE_PLUGIN_FILE', __FILE__);
}

// 3. Load global plugin constants.
require_once __DIR__ . '/config/constants.php';

// 4. Verify minimum PHP version before loading PHP 8.0+ typed classes.
if (version_compare(PHP_VERSION, CRAE_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    'ClicReport Assessment Engine requires PHP %s or higher. Current version: %s.',
                    CRAE_MIN_PHP_VERSION,
                    PHP_VERSION
                )
            )
        );
    });
    return;
}

// 5. Load Composer autoloader if present, otherwise use our built-in PSR-4 Autoloader.
if (is_readable(CRAE_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once CRAE_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    require_once CRAE_PLUGIN_DIR . 'includes/Core/Autoloader.php';
    \ClicReport\AssessmentEngine\Core\Autoloader::register();
}

// 6. Register WordPress Activation & Deactivation lifecycle hooks.
register_activation_hook(
    CRAE_PLUGIN_FILE,
    [\ClicReport\AssessmentEngine\Core\Activator::class, 'activate']
);

register_deactivation_hook(
    CRAE_PLUGIN_FILE,
    [\ClicReport\AssessmentEngine\Core\Deactivator::class, 'deactivate']
);

/**
 * Global accessor for the ClicReport Plugin singleton instance.
 *
 * @return \ClicReport\AssessmentEngine\Core\Plugin
 */
function crae(): \ClicReport\AssessmentEngine\Core\Plugin
{
    return \ClicReport\AssessmentEngine\Core\Plugin::instance();
}

// 7. Boot the plugin once WordPress has loaded all active plugins.
add_action('plugins_loaded', static function (): void {
    crae()->boot();
}, 10);
