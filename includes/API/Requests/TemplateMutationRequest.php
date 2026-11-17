<?php
/**
 * Request Validator for Admin Template Metadata Updates (Mass-Assignment Protected)
 *
 * @package ClicReport\AssessmentEngine\API\Requests
 */

namespace ClicReport\AssessmentEngine\API\Requests;

defined( 'ABSPATH' ) || exit;

final class TemplateMutationRequest {

    public static function args(): array {
        return [
            'template_name'   => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'publisher'       => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'category'        => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'intro_narrative' => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'wp_kses_post',
            ],
            'is_active'       => [
                'type'     => 'boolean',
                'required' => false,
            ],
        ];
    }

    /**
     * Whitelist writable fields explicitly to prevent mass-assignment.
     */
    public function extractAllowedUpdates( \WP_REST_Request $request, array $existingTemplate ): array {
        $allowed = [ 'template_name', 'publisher', 'category', 'intro_narrative', 'is_active' ];
        $updated = $existingTemplate;

        foreach ( $allowed as $field ) {
            if ( ! $request->has_param( $field ) ) {
                continue;
            }

            $val               = $request->get_param( $field );
            $updated[ $field ] = 'is_active' === $field ? ( $val ? 1 : 0 ) : $val;
        }

        return $updated;
    }
}

