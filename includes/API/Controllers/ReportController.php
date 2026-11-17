<?php
/**
 * REST Controller for Clinical Report Generation & Execution Audit Logs
 *
 * Endpoints:
 *   POST /wp-json/clicreport/v1/reports/generate
 *   GET  /wp-json/clicreport/v1/audit-logs
 *
 * @package ClicReport\AssessmentEngine\API\Controllers
 */

namespace ClicReport\AssessmentEngine\API\Controllers;

use ClicReport\AssessmentEngine\API\Requests\GenerateReportRequest;
use ClicReport\AssessmentEngine\API\Responses\ApiResponse;
use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository;
use ClicReport\AssessmentEngine\Security\RateLimiter;
use ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine;

defined( 'ABSPATH' ) || exit;

final class ReportController extends AbstractRestController {

    public function __construct(
        CapabilityManager $capabilities,
        RateLimiter $rateLimiter,
        private DatabaseReportEngine $engine,
        private AuditLogRepository $auditRepo,
        private GenerateReportRequest $requestValidator,
        private ?\ClicReport\AssessmentEngine\Security\ClientGuard $clientGuard = null,
        private ?\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService $tokenService = null
    ) {
        parent::__construct( $capabilities, $rateLimiter );
    }

    public function registerRoutes(): void {
        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/reports/generate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generate' ],
                'permission_callback' => [ $this->capabilities, 'canGenerateReports' ],
                'args'                => GenerateReportRequest::args(),
            ]
        );

        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/audit-logs',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'auditLogs' ],
                'permission_callback' => [ $this->capabilities, 'canViewAuditLogs' ],
                'args'                => [
                    'page'     => [
                        'type'              => 'integer',
                        'default'           => 1,
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'per_page' => [
                        'type'              => 'integer',
                        'default'           => 25,
                        'minimum'           => 1,
                        'maximum'           => 100,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    public function generate( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        // Enforce anti-Postman & browser request verification
        if ( null !== $this->clientGuard ) {
            $clientCheck = $this->clientGuard->verifyBrowserRequest();
            if ( is_wp_error( $clientCheck ) ) {
                return $clientCheck;
            }

            $subCheck = $this->clientGuard->verifySubscription( get_current_user_id() );
            if ( is_wp_error( $subCheck ) ) {
                return $subCheck;
            }
        }

        // Enforce one-time Psivey-Token consumption
        if ( null !== $this->tokenService ) {
            $psiveyToken = (string) ( $request->get_header( 'Psivey-Token' ) ?: ( $request->get_header( 'X-Psivey-Token' ) ?: $request->get_param( 'psivey_token' ) ) );
            $tokenCheck  = $this->tokenService->verifyAndConsumeToken( $psiveyToken, get_current_user_id() );
            if ( is_wp_error( $tokenCheck ) ) {
                return $tokenCheck;
            }
        }

        $settings = get_option( 'crae_settings', [] );
        $limit    = max( 1, (int) ( $settings['rate_limit_per_minute'] ?? 60 ) );

        $rateCheck = $this->enforceRateLimit( 'reports_generate', $limit, 60 );
        if ( is_wp_error( $rateCheck ) ) {
            return $rateCheck;
        }

        try {
            $payload = $this->requestValidator->sanitizePayload( $request );
            $result  = $this->engine->generate(
                $payload,
                get_current_user_id(),
                $this->rateLimiter->getClientIp()
            );

            // Issue next one-time Psivey-Token for subsequent report generation
            if ( null !== $this->tokenService && get_current_user_id() > 0 ) {
                $user = wp_get_current_user();
                if ( $user->exists() ) {
                    $nextIssued = $this->tokenService->issueForUser( $user );
                    $result['next_psivey_token'] = $nextIssued['token'] ?? '';
                }
            }

            return ApiResponse::success(
                $result,
                200,
                [ 'execution_ms' => $result['execution_time_ms'] ?? 0 ]
            );
        } catch ( \Throwable $e ) {
            return ApiResponse::fromException( $e );
        }
    }

    public function auditLogs( \WP_REST_Request $request ): \WP_REST_Response {
        $page    = max( 1, (int) $request->get_param( 'page' ) );
        $perPage = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );

        return ApiResponse::success( $this->auditRepo->paginate( $page, $perPage ) );
    }
}

