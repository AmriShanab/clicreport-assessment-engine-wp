<?php
/**
 * Plugin Permission-to-WordPress Capability Map
 *
 * Uses built-in WordPress capabilities so no custom user roles
 * or wp_user_roles database modifications are required.
 *
 * @package ClicReport\AssessmentEngine
 */

defined( 'ABSPATH' ) || exit;

return [
    // Logged-in users (Subscriber+) can view templates and generate clinical reports.
    'view_templates'   => 'read',
    'generate_reports' => 'read',

    // Site Administrators manage templates, system settings, and audit logs.
    'manage_templates' => 'manage_options',
    'view_audit_logs'  => 'manage_options',
    'manage_settings'  => 'manage_options',
];
