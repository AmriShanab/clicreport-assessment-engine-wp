<?php
/**
 * Strict REST API CORS Policy Manager
 *
 * Replaces legacy wildcard Access-Control-Allow-Origin: * with explicit
 * origin verification bound to the WordPress site URL and configured allowlist.
 *
 * @package ClicReport\AssessmentEngine\Security
 */

namespace ClicReport\AssessmentEngine\Security;

defined( 'ABSPATH' ) || exit;

final class CorsManager {

    /**
     * Register WordPress REST CORS filters.
     */
    public function register(): void {
        add_filter( 'rest_pre_serve_request', [ $this, 'applyStrictCorsHeaders' ], 15, 4 );
    }

    /**
     * Send explicit Origin headers only for allowlisted origins on ClicReport REST routes.
     */
    public function applyStrictCorsHeaders( bool $served, \WP_HTTP_Response $result, \WP_REST_Request $request, \WP_REST_Server $server ): bool {
        $route = $request->get_route();
        if ( ! str_starts_with( $route, '/' . CRAE_REST_NAMESPACE ) && ! str_starts_with( $route, '/' . CRAE_PSIVEY_REST_NAMESPACE ) ) {
            return $served;
        }

        $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['HTTP_ORIGIN'] ) ) : '';
        if ( '' === $origin ) {
            return $served;
        }

        if ( in_array( rtrim( $origin, '/' ), $this->getAllowedOrigins(), true ) ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
            header( 'Vary: Origin' );
        }

        return $served;
    }

    /**
     * @return string[]
     */
    private function getAllowedOrigins(): array {
        $siteOrigin = rtrim( (string) home_url(), '/' );
        $settings   = get_option( 'crae_settings', [] );
        $configured = $settings['allowed_cors_origins'] ?? [];

        $origins = [ $siteOrigin ];
        if ( is_array( $configured ) ) {
            foreach ( $configured as $item ) {
                $clean = rtrim( esc_url_raw( (string) $item ), '/' );
                if ( '' !== $clean ) {
                    $origins[] = $clean;
                }
            }
        }

        return array_values( array_unique( $origins ) );
    }
}

