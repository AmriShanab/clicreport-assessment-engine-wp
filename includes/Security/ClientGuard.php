<?php
/**
 * Strict Client Guard & Anti-Scraping / Anti-Postman Verification Service
 *
 * Protects subscription-based clinical assessment endpoints against direct
 * API testing tools (Postman, Insomnia, curl, Python, bots) and verifies
 * user subscription entitlement before clinical narrative generation.
 *
 * @package ClicReport\AssessmentEngine\Security
 */

namespace ClicReport\AssessmentEngine\Security;

defined( 'ABSPATH' ) || exit;

final class ClientGuard {

    /**
     * Known API testing, scraping, and automated tool User-Agent signatures.
     *
     * @var string[]
     */
    private const BLOCKED_USER_AGENTS = [
        'postmanruntime',
        'postman',
        'insomnia',
        'curl',
        'wget',
        'python-requests',
        'python-urllib',
        'aiohttp',
        'httpx',
        'axios',
        'node-fetch',
        'got',
        'superagent',
        'go-http-client',
        'java/',
        'apache-httpclient',
        'okhttp',
        'httpie',
        'paw',
        'thunder-client',
        'rest-client',
        'playwright',
        'puppeteer',
        'headlesschrome',
        'phantomjs',
        'selenium',
    ];

    /**
     * Validate that the incoming request is originating from a trusted,
     * genuine browser session on the current site rather than Postman or automated tools.
     *
     * @return true|\WP_Error True if verified, WP_Error with 403 on block.
     */
    public function verifyBrowserRequest(): true|\WP_Error {
        // Allow programmatic bypass for unit tests or webhook integrations if filtered
        if ( apply_filters( 'crae_bypass_client_guard', false ) ) {
            return true;
        }

        // 1. Verify User-Agent exists and does not match banned API testing tools
        $rawUserAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $userAgent    = strtolower( trim( $rawUserAgent ) );

        if ( '' === $userAgent ) {
            return new \WP_Error(
                'crae_client_blocked_empty_ua',
                __( 'Direct client access without a valid browser user-agent is not permitted.', 'clicreport-assessment-engine' ),
                [ 'status' => 403 ]
            );
        }

        foreach ( self::BLOCKED_USER_AGENTS as $signature ) {
            if ( str_contains( $userAgent, $signature ) ) {
                return new \WP_Error(
                    'crae_client_blocked_tool',
                    sprintf(
                        __( 'Access via API client or automated tool (%s) is strictly forbidden. This assessment system requires an authorized web browser session.', 'clicreport-assessment-engine' ),
                        esc_html( $signature )
                    ),
                    [ 'status' => 403 ]
                );
            }
        }

        // 2. Verify Origin and/or Referer to ensure request originates from the local WordPress site
        $origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
        $referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';

        // If both are missing on a POST request, block direct API execution
        if ( '' === $origin && '' === $referer ) {
            return new \WP_Error(
                'crae_client_missing_origin',
                __( 'Direct endpoint execution without browser Origin or Referer is blocked. Access is restricted to the web application interface.', 'clicreport-assessment-engine' ),
                [ 'status' => 403 ]
            );
        }

        $allowedHosts = $this->getAllowedHosts();

        if ( '' !== $origin ) {
            $originHost = strtolower( (string) parse_url( $origin, PHP_URL_HOST ) );
            if ( ! empty( $originHost ) && ! in_array( $originHost, $allowedHosts, true ) ) {
                return new \WP_Error(
                    'crae_client_invalid_origin',
                    __( 'Cross-origin request rejected. Access is restricted to authorized site domains.', 'clicreport-assessment-engine' ),
                    [ 'status' => 403 ]
                );
            }
        }

        if ( '' !== $referer ) {
            $refererHost = strtolower( (string) parse_url( $referer, PHP_URL_HOST ) );
            if ( ! empty( $refererHost ) && ! in_array( $refererHost, $allowedHosts, true ) ) {
                return new \WP_Error(
                    'crae_client_invalid_referer',
                    __( 'Referer host does not match authorized site domain.', 'clicreport-assessment-engine' ),
                    [ 'status' => 403 ]
                );
            }
        }

        // 3. Reject cross-site metadata if sent by modern browsers
        if ( isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ) {
            $fetchSite = strtolower( trim( (string) $_SERVER['HTTP_SEC_FETCH_SITE'] ) );
            if ( 'cross-site' === $fetchSite ) {
                return new \WP_Error(
                    'crae_client_cross_site',
                    __( 'Sec-Fetch-Site cross-site request is blocked.', 'clicreport-assessment-engine' ),
                    [ 'status' => 403 ]
                );
            }
        }

        return true;
    }

    /**
     * Check whether an authenticated user is a valid subscriber.
     * All logged-in users on this platform are recognized as subscribers.
     *
     * @param int $userId WordPress user ID.
     * @return true|\WP_Error True if authenticated subscriber, WP_Error with 401 if unauthenticated.
     */
    public function verifySubscription( int $userId ): true|\WP_Error {
        if ( $userId <= 0 || ! is_user_logged_in() ) {
            return new \WP_Error(
                'crae_auth_required',
                __( 'You must be logged in as an authorized subscriber to access assessment features.', 'clicreport-assessment-engine' ),
                [ 'status' => 401 ]
            );
        }

        // All authenticated users are valid subscribers
        return true;
    }

    /**
     * Return array of allowed hostnames (e.g. ['clicreport.local', 'localhost']).
     *
     * @return string[]
     */
    public function getAllowedHosts(): array {
        $hosts = [];

        $siteUrlHost = parse_url( site_url(), PHP_URL_HOST );
        if ( ! empty( $siteUrlHost ) ) {
            $hosts[] = strtolower( (string) $siteUrlHost );
        }

        $homeUrlHost = parse_url( home_url(), PHP_URL_HOST );
        if ( ! empty( $homeUrlHost ) ) {
            $hosts[] = strtolower( (string) $homeUrlHost );
        }

        if ( isset( $_SERVER['HTTP_HOST'] ) ) {
            $serverHost = preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] );
            if ( ! empty( $serverHost ) ) {
                $hosts[] = strtolower( $serverHost );
            }
        }

        // Include any additional allowed origins configured in settings
        $settings   = get_option( 'crae_settings', [] );
        $configured = $settings['allowed_cors_origins'] ?? [];
        if ( is_array( $configured ) ) {
            foreach ( $configured as $item ) {
                $h = parse_url( (string) $item, PHP_URL_HOST );
                if ( ! empty( $h ) ) {
                    $hosts[] = strtolower( (string) $h );
                }
            }
        }

        return array_values( array_unique( $hosts ) );
    }
}
