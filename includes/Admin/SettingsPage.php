<?php
/**
 * WordPress Settings API Handler for ClicReport Assessment Engine
 *
 * @package ClicReport\AssessmentEngine\Admin
 */

namespace ClicReport\AssessmentEngine\Admin;

use ClicReport\AssessmentEngine\Authorization\CapabilityManager;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

    public const OPTION_GROUP = 'crae_settings_group';
    public const OPTION_NAME  = 'crae_settings';
    public const PAGE_SLUG    = 'clicreport-settings';

    public function __construct( private CapabilityManager $capabilities ) {}

    /**
     * Register WordPress Settings API option, sections, and sanitized fields.
     */
    public function register(): void {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitizeSettings' ],
                'default'           => $this->getDefaults(),
            ]
        );

        add_settings_section(
            'crae_psivey_section',
            __( 'Psivey Cloud & JWT Authentication', 'clicreport-assessment-engine' ),
            static function (): void {
                echo '<p>' . esc_html__( 'Configure external Psivey API connectivity and short-lived HMAC-SHA256 token signing. Constants defined in wp-config.php (PSIVEY_BASE_URL, PSIVEY_JWT_SECRET) take precedence.', 'clicreport-assessment-engine' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'psivey_base_url',
            __( 'Psivey Base URL', 'clicreport-assessment-engine' ),
            [ $this, 'renderTextField' ],
            self::PAGE_SLUG,
            'crae_psivey_section',
            [
                'key'         => 'psivey_base_url',
                'type'        => 'url',
                'placeholder' => 'https://api.psivey.com',
                'locked'      => defined( 'PSIVEY_BASE_URL' ),
                'note'        => defined( 'PSIVEY_BASE_URL' )
                    ? __( 'Overridden by PSIVEY_BASE_URL constant in wp-config.php.', 'clicreport-assessment-engine' )
                    : __( 'Upstream HTTPS endpoint for Psivey AI & Stripe services.', 'clicreport-assessment-engine' ),
            ]
        );

        add_settings_field(
            'psivey_jwt_secret',
            __( 'Psivey JWT Signing Secret', 'clicreport-assessment-engine' ),
            [ $this, 'renderSecretField' ],
            self::PAGE_SLUG,
            'crae_psivey_section',
            [
                'key'    => 'psivey_jwt_secret',
                'locked' => defined( 'PSIVEY_JWT_SECRET' ),
            ]
        );

        add_settings_field(
            'psivey_jwt_ttl_seconds',
            __( 'JWT Token Lifetime (Seconds)', 'clicreport-assessment-engine' ),
            [ $this, 'renderNumberField' ],
            self::PAGE_SLUG,
            'crae_psivey_section',
            [
                'key'  => 'psivey_jwt_ttl_seconds',
                'min'  => 60,
                'max'  => 86400,
                'note' => __( 'Allowed range: 60–86400 seconds (default: 900 = 15 minutes).', 'clicreport-assessment-engine' ),
            ]
        );

        add_settings_section(
            'crae_security_section',
            __( 'Security, Notifications & Data Lifecycle', 'clicreport-assessment-engine' ),
            static function (): void {
                echo '<p>' . esc_html__( 'Control API rate limiting, contact notifications, and uninstall behavior.', 'clicreport-assessment-engine' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            'rate_limit_per_minute',
            __( 'Report API Rate Limit (req/min)', 'clicreport-assessment-engine' ),
            [ $this, 'renderNumberField' ],
            self::PAGE_SLUG,
            'crae_security_section',
            [
                'key'  => 'rate_limit_per_minute',
                'min'  => 1,
                'max'  => 1000,
                'note' => __( 'Maximum report generations per minute per authenticated user/IP.', 'clicreport-assessment-engine' ),
            ]
        );

        add_settings_field(
            'contact_recipient_email',
            __( 'Contact Form Recipient Email', 'clicreport-assessment-engine' ),
            [ $this, 'renderTextField' ],
            self::PAGE_SLUG,
            'crae_security_section',
            [
                'key'         => 'contact_recipient_email',
                'type'        => 'email',
                'placeholder' => (string) get_option( 'admin_email', '' ),
                'locked'      => false,
                'note'        => __( 'Leave blank to use the WordPress Site Admin Email.', 'clicreport-assessment-engine' ),
            ]
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __( 'Delete All Plugin Tables on Uninstall', 'clicreport-assessment-engine' ),
            [ $this, 'renderCheckboxField' ],
            self::PAGE_SLUG,
            'crae_security_section',
            [
                'key'  => 'delete_data_on_uninstall',
                'note' => __( 'When unchecked (recommended), custom assessment tables and templates are preserved if the plugin is deleted.', 'clicreport-assessment-engine' ),
            ]
        );
    }

    /**
     * Render the Settings Admin View.
     */
    public function render(): void {
        if ( ! $this->capabilities->canManageSettings() ) {
            wp_die( esc_html__( 'You do not have permission to manage ClicReport settings.', 'clicreport-assessment-engine' ), 403 );
        }

        $optionGroup = self::OPTION_GROUP;
        $pageSlug    = self::PAGE_SLUG;

        include CRAE_PLUGIN_DIR . 'resources/views/admin/settings.php';
    }

    /**
     * Sanitize and preserve existing secret when the password input is submitted empty.
     *
     * @param mixed $input
     * @return array<string, mixed>
     */
    public function sanitizeSettings( mixed $input ): array {
        $defaults = $this->getDefaults();
        $existing = get_option( self::OPTION_NAME, $defaults );
        if ( ! is_array( $existing ) ) {
            $existing = $defaults;
        }
        $raw = is_array( $input ) ? $input : [];

        $baseUrl = isset( $raw['psivey_base_url'] ) ? esc_url_raw( trim( (string) $raw['psivey_base_url'] ) ) : '';
        if ( '' === $baseUrl ) {
            $baseUrl = (string) $defaults['psivey_base_url'];
        }

        $secret = (string) ( $existing['psivey_jwt_secret'] ?? '' );
        if ( ! empty( $raw['psivey_jwt_secret'] ) ) {
            $secret = trim( (string) $raw['psivey_jwt_secret'] );
        } elseif ( ! empty( $raw['clear_psivey_jwt_secret'] ) ) {
            $secret = '';
        }

        $ttl       = max( 60, min( 86400, (int) ( $raw['psivey_jwt_ttl_seconds'] ?? $defaults['psivey_jwt_ttl_seconds'] ) ) );
        $rateLimit = max( 1, min( 1000, (int) ( $raw['rate_limit_per_minute'] ?? $defaults['rate_limit_per_minute'] ) ) );
        $email     = isset( $raw['contact_recipient_email'] ) ? sanitize_email( (string) $raw['contact_recipient_email'] ) : '';

        return [
            'psivey_base_url'          => $baseUrl,
            'psivey_jwt_secret'        => $secret,
            'psivey_jwt_ttl_seconds'   => $ttl,
            'rate_limit_per_minute'    => $rateLimit,
            'contact_recipient_email'  => $email,
            'delete_data_on_uninstall' => ! empty( $raw['delete_data_on_uninstall'] ),
        ];
    }

    /**
     * @param array<string, mixed> $args
     */
    public function renderTextField( array $args ): void {
        $settings = $this->getSettings();
        $key      = (string) $args['key'];
        $type     = (string) ( $args['type'] ?? 'text' );
        $value    = (string) ( $settings[ $key ] ?? '' );
        $locked   = ! empty( $args['locked'] );

        printf(
            '<input type="%s" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" %s />',
            esc_attr( $type ),
            esc_attr( self::OPTION_NAME ),
            esc_attr( $key ),
            esc_attr( $value ),
            esc_attr( (string) ( $args['placeholder'] ?? '' ) ),
            $locked ? 'readonly="readonly"' : ''
        );

        if ( ! empty( $args['note'] ) ) {
            echo '<p class="description">' . esc_html( (string) $args['note'] ) . '</p>';
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    public function renderSecretField( array $args ): void {
        $settings  = $this->getSettings();
        $key       = (string) $args['key'];
        $hasSecret = '' !== (string) ( $settings[ $key ] ?? '' );
        $locked    = ! empty( $args['locked'] );

        printf(
            '<input type="password" name="%s[%s]" value="" class="regular-text" autocomplete="new-password" placeholder="%s" %s />',
            esc_attr( self::OPTION_NAME ),
            esc_attr( $key ),
            esc_attr( $hasSecret ? '•••••••• (stored — leave blank to keep)' : 'Falls back to wp_salt(auth) when blank' ),
            $locked ? 'readonly="readonly"' : ''
        );

        if ( $hasSecret && ! $locked ) {
            printf(
                ' <label style="margin-left:8px;"><input type="checkbox" name="%s[clear_psivey_jwt_secret]" value="1" /> %s</label>',
                esc_attr( self::OPTION_NAME ),
                esc_html__( 'Clear stored secret', 'clicreport-assessment-engine' )
            );
        }

        if ( ! empty( $args['note'] ) ) {
            echo '<p class="description">' . esc_html( (string) $args['note'] ) . '</p>';
        } elseif ( $locked ) {
            echo '<p class="description">' . esc_html__( 'Overridden by PSIVEY_JWT_SECRET constant in wp-config.php.', 'clicreport-assessment-engine' ) . '</p>';
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    public function renderNumberField( array $args ): void {
        $settings = $this->getSettings();
        $key      = (string) $args['key'];
        $value    = (int) ( $settings[ $key ] ?? 0 );

        printf(
            '<input type="number" name="%s[%s]" value="%d" min="%d" max="%d" class="small-text" />',
            esc_attr( self::OPTION_NAME ),
            esc_attr( $key ),
            $value,
            (int) ( $args['min'] ?? 0 ),
            (int) ( $args['max'] ?? 999999 )
        );

        if ( ! empty( $args['note'] ) ) {
            echo '<p class="description">' . esc_html( (string) $args['note'] ) . '</p>';
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    public function renderCheckboxField( array $args ): void {
        $settings = $this->getSettings();
        $key      = (string) $args['key'];
        $checked  = ! empty( $settings[ $key ] );

        printf(
            '<label><input type="checkbox" name="%s[%s]" value="1" %s /> %s</label>',
            esc_attr( self::OPTION_NAME ),
            esc_attr( $key ),
            checked( $checked, true, false ),
            esc_html__( 'Enable', 'clicreport-assessment-engine' )
        );

        if ( ! empty( $args['note'] ) ) {
            echo '<p class="description">' . esc_html( (string) $args['note'] ) . '</p>';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array {
        $stored = get_option( self::OPTION_NAME, [] );
        return wp_parse_args( is_array( $stored ) ? $stored : [], $this->getDefaults() );
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaults(): array {
        $schema = require CRAE_PLUGIN_DIR . 'config/settings-schema.php';
        return is_array( $schema['defaults'] ?? null ) ? $schema['defaults'] : [];
    }
}

