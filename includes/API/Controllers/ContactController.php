<?php
/**
 * Secure Contact Form REST Controller (Replaces legacy mail.php)
 *
 * Endpoint: POST /wp-json/clicreport/v1/contact
 *
 * @package ClicReport\AssessmentEngine\API\Controllers
 */

namespace ClicReport\AssessmentEngine\API\Controllers;

use ClicReport\AssessmentEngine\API\Responses\ApiResponse;
use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Security\RateLimiter;
use ClicReport\AssessmentEngine\Services\Mail\NotificationService;

defined( 'ABSPATH' ) || exit;

final class ContactController extends AbstractRestController {

    public function __construct(
        CapabilityManager $capabilities,
        RateLimiter $rateLimiter,
        private NotificationService $mailer
    ) {
        parent::__construct( $capabilities, $rateLimiter );
    }

    public function registerRoutes(): void {
        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/contact',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'submit' ],
                'permission_callback' => '__return_true', // Public form protected by rate limiting & input validation.
                'args'                => [
                    'name'    => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'email'   => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_email',
                        'validate_callback' => static fn( $val ): bool => (bool) is_email( (string) $val ),
                    ],
                    'subject' => [
                        'type'              => 'string',
                        'required'          => false,
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'message' => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                ],
            ]
        );
    }

    public function submit( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $rateCheck = $this->enforceRateLimit( 'contact_submit', 5, 600 );
        if ( is_wp_error( $rateCheck ) ) {
            return $rateCheck;
        }

        try {
            $sent = $this->mailer->sendContactMessage(
                (string) $request->get_param( 'name' ),
                (string) $request->get_param( 'email' ),
                (string) $request->get_param( 'subject' ),
                (string) $request->get_param( 'message' )
            );

            if ( ! $sent ) {
                return ApiResponse::error( 'crae_mail_failed', 'Unable to dispatch message right now.', 500 );
            }

            return ApiResponse::success( [ 'sent' => true ] );
        } catch ( \Throwable $e ) {
            return ApiResponse::fromException( $e );
        }
    }
}

