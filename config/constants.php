<?php

/**
 * Global Plugin Constants
 * 
 * @package ClicReport\AssessmentEngine
 */

defined('ABSPATH') || exit;

define( 'CRAE_VERSION', '1.1.0' );
define( 'CRAE_DB_VERSION', '1.0.0' );
define( 'CRAE_SLUG', 'clicreport-assessment-engine' );
define( 'CRAE_TEXT_DOMAIN', 'clicreport-assessment-engine' );

define( 'CRAE_MIN_PHP_VERSION', '8.0' );
define( 'CRAE_MIN_WP_VERSION', '6.2' );

define( 'CRAE_TABLE_PREFIX', 'crae_' );
define( 'CRAE_REST_NAMESPACE', 'clicreport/v1' );
define( 'CRAE_PSIVEY_REST_NAMESPACE', 'psivey/v1' );

define( 'CRAE_PLUGIN_DIR', plugin_dir_path( CRAE_PLUGIN_FILE ) );
define( 'CRAE_PLUGIN_URL', plugin_dir_url( CRAE_PLUGIN_FILE ) );
define( 'CRAE_PLUGIN_BASENAME', plugin_basename( CRAE_PLUGIN_FILE ) );
