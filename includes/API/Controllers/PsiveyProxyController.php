<?php
/**
 * REST Controller for Psivey JWT Auth & Legacy Compatibility Proxy
 *
 * Preserves legacy endpoint:
 *   POST /wp-json/psivey/v1/generate-token  (returns { token, expires_in, expires_at })
 * Plus new endpoints:
 *   POST /wp-json/clicreport/v1/auth/token
 *   POST /wp-json/clicreport/v1/auth/revoke
 *
 * @package ClicReport\AssessmentEngine\API\Controllers
 */

namespace ClicReport\AssessmentEngine\API\Controllers;

use ClicReport\AssessmentEngine\API\Responses\ApiResponse;
use ClicReport\AssessmentEngine\Authentication\PsiveyTokenService;
use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Security\RateLimiter;

defined( 'ABSPATH' ) || exit;

final class PsiveyProxyController extends AbstractRestController {

    public function __construct(
        CapabilityManager $capabilities,
        RateLimiter $rateLimiter,
        private PsiveyTokenService $tokenService
    ) {
        parent::__construct( $capabilities, $rateLimiter );
    }

    public function registerRoutes(): void {
        // Legacy compatibility route consumed by psivey-chat, psivey-credits, psivey-report-upload.
        register_rest_route(
            CRAE_PSIVEY_REST_NAMESPACE,
            '/generate-token',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generateTokenLegacy' ],
                'permission_callback' => [ $this->capabilities, 'canGenerateReports' ],
            ]
        );

        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/auth/token',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generateToken' ],
                'permission_callback' => [ $this->capabilities, 'canGenerateReports' ],
            ]
        );

        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/auth/revoke',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'revokeTokens' ],
                'permission_callback' => [ $this->capabilities, 'canGenerateReports' ],
            ]
        );
    }

    /**
     * Top-level { token } payload for legacy Psivey frontend templates.
     */
    public function generateTokenLegacy(): \WP_REST_Response|\WP_Error {
        $rateCheck = $this->enforceRateLimit( 'psivey_token', 15, 60 );
        if ( is_wp_error( $rateCheck ) ) {
            return $rateCheck;
        }

        try {
            $tokenData = $this->tokenService->issueForUser( wp_get_current_user() );
            return new \WP_REST_Response( $tokenData, 200 );
        } catch ( \Throwable $e ) {
            return ApiResponse::fromException( $e, 401 );
        }
    }

    public function generateToken(): \WP_REST_Response|\WP_Error {
        $rateCheck = $this->enforceRateLimit( 'psivey_token', 15, 60 );
        if ( is_wp_error( $rateCheck ) ) {
            return $rateCheck;
        }

        try {
            return ApiResponse::success( $this->tokenService->issueForUser( wp_get_current_user() ) );
        } catch ( \Throwable $e ) {
            return ApiResponse::fromException( $e, 401 );
        }
    }

    public function revokeTokens(): \WP_REST_Response {
        $this->tokenService->revokeUserTokens( get_current_user_id() );
        return ApiResponse::success( [ 'revoked' => true ] );
    }
}

