<?php
/**
 * Request Schema & Sanitizer for Clinical Report Generation
 *
 * @package ClicReport\AssessmentEngine\API\Requests
 */

namespace ClicReport\AssessmentEngine\API\Requests;

use ClicReport\AssessmentEngine\Security\InputSanitizer;

defined( 'ABSPATH' ) || exit;

final class GenerateReportRequest {

    public function __construct( private InputSanitizer $sanitizer ) {}

    /**
     * WordPress REST API argument schema definitions.
     */
    public static function args(): array {
        return [
            'template_id'     => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'template_name'   => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'assessment_type' => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'first_name'      => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'gender'          => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Extract and recursively sanitize assessment parameters from WP_REST_Request.
     *
     * @return array<string, mixed>
     */
    public function sanitizePayload( \WP_REST_Request $request ): array {
        $params = $request->get_params();
        $clean  = $this->sanitizer->sanitizeArray( is_array( $params ) ? $params : [] );

        $rawSlug = (string) ( $clean['template_name'] ?? $clean['template_id'] ?? $clean['assessment_type'] ?? '' );
        if ( '' !== $rawSlug ) {
            $clean['template_name'] = $this->sanitizer->sanitizeTemplateSlug( $rawSlug );
        }

        if ( isset( $clean['gender'] ) ) {
            $clean['gender'] = $this->sanitizer->sanitizePronoun( (string) $clean['gender'] );
        }

        return $clean;
    }
}

