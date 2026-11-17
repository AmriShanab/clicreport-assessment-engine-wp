<?php
/**
 * Default Plugin Settings Schema (stored in wp_options as 'crae_settings')
 *
 * @package ClicReport\AssessmentEngine
 */

defined('ABSPATH') || exit;

return [
    'defaults' => [
        'psivey_base_url' => 'https://api.psivey.com',
        'psivey_jwt_secret' => '',
        'psivey_jwt_ttl_seconds' => 900,
        'rate_limit_per_minute' => 60,
        'contact_recipient_email' => '',
        'delete_data_on_uninstall' => false,
    ],
];