<?php
/**
 * WordPress REST Authentication Bridge
 *
 * Authenticates external Bearer JWT requests via 'determine_current_user' while preserving
 * native WordPress Cookie + X-WP-Nonce and Application Password authentication.
 *
 * @package ClicReport\AssessmentEngine\Authentication
 */

namespace ClicReport\AssessmentEngine\Authentication;

defined( 'ABSPATH' ) || exit;

final class AuthManager {

    public function __construct( private PsiveyTokenService $tokenService ) {}

    public function register(): void {
        add_filter( 'determine_current_user', [ $this, 'authenticateBearerToken' ], 20 );
    }

    /**
     * Resolve current user from Authorization: Bearer <jwt> header on ClicReport REST requests.
     */
    public function authenticateBearerToken( int|bool $userId ): int|bool {
        if ( ! empty( $userId ) ) {
            return $userId;
        }

        if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
            return $userId;
        }

        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ( ! str_contains( $uri, CRAE_REST_NAMESPACE ) && ! str_contains( $uri, CRAE_PSIVEY_REST_NAMESPACE ) ) {
            return $userId;
        }

        $authHeader = $this->extractAuthorizationHeader();
        if ( ! str_starts_with( $authHeader, 'Bearer ' ) ) {
            return $userId;
        }

        $jwt    = trim( substr( $authHeader, 7 ) );
        $claims = $this->tokenService->verifyToken( $jwt );
        if ( is_wp_error( $claims ) ) {
            return $userId;
        }

        $user = get_user_by( 'id', (int) $claims['sub'] );
        return ( $user && $user->exists() ) ? (int) $user->ID : $userId;
    }

    private function extractAuthorizationHeader(): string {
        if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            return sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_AUTHORIZATION'] ) );
        }
        if ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            return sanitize_text_field( wp_unslash( (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
        }
        return '';
    }
}

