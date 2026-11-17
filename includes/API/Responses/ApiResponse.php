<?php
/**
 * Standardized REST API Response Factory
 *
 * Ensures consistent JSON envelopes and prevents leaking internal stack traces or SQL errors.
 *
 * @package ClicReport\AssessmentEngine\API\Responses
 */

namespace ClicReport\AssessmentEngine\API\Responses;

defined( 'ABSPATH' ) || exit;

final class ApiResponse {

    /**
     * Return a standardized 2xx REST response.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function success( array $data, int $statusCode = 200, array $meta = [] ): \WP_REST_Response {
        $payload = [
            'success' => true,
            'status'  => 'success',
            'data'    => $data,
        ];

        if ( ! empty( $meta ) ) {
            $payload['meta'] = $meta;
        }

        return new \WP_REST_Response( $payload, $statusCode );
    }

    /**
     * Return a safe WP_Error without exposing internal SQL or filesystem paths.
     */
    public static function error( string $code, string $message, int $statusCode = 400, array $extra = [] ): \WP_Error {
        return new \WP_Error(
            sanitize_key( $code ),
            sanitize_text_field( $message ),
            array_merge( [ 'status' => $statusCode ], $extra )
        );
    }

    /**
     * Convert an internal exception into a safe REST WP_Error.
     */
    public static function fromException( \Throwable $e, int $fallbackStatus = 500 ): \WP_Error {
        if ( $e instanceof \InvalidArgumentException ) {
            $status = str_contains( strtolower( $e->getMessage() ), 'not found' ) ? 404 : 400;
            return self::error( 'crae_invalid_request', $e->getMessage(), $status );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[ClicReport REST Error] ' . $e->getMessage() );
        }

        return self::error( 'crae_internal_error', 'An unexpected error occurred while processing the request.', $fallbackStatus );
    }
}
