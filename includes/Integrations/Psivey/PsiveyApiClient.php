<?php
/**
 * WordPress HTTP Client for External Psivey Cloud Services
 *
 * @package ClicReport\AssessmentEngine\Integrations\Psivey
 */

namespace ClicReport\AssessmentEngine\Integrations\Psivey;

defined( 'ABSPATH' ) || exit;

final class PsiveyApiClient {

    /**
     * Dispatch an authenticated JSON request to the configured Psivey backend URL.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>|\WP_Error
     */
    public function request( string $method, string $path, string $bearerToken, array $body = [] ): array|\WP_Error {
        $baseUrl = $this->getBaseUrl();
        $url     = rtrim( $baseUrl, '/' ) . '/' . ltrim( $path, '/' );

        if ( ! wp_http_validate_url( $url ) ) {
            return new \WP_Error( 'crae_invalid_upstream_url', 'Configured Psivey endpoint URL is invalid.', [ 'status' => 500 ] );
        }

        $args = [
            'method'  => strtoupper( $method ),
            'timeout' => 15,
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
            ],
        ];

        if ( ! empty( $body ) && in_array( $args['method'], [ 'POST', 'PUT', 'PATCH' ], true ) ) {
            $args['body'] = (string) wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );
        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'crae_upstream_unreachable', 'Unable to reach Psivey service.', [ 'status' => 502 ] );
        }

        $statusCode = (int) wp_remote_retrieve_response_code( $response );
        $decoded    = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( $statusCode >= 400 ) {
            $msg = is_array( $decoded ) && ! empty( $decoded['message'] )
                ? sanitize_text_field( (string) $decoded['message'] )
                : 'Upstream service returned an error.';
            return new \WP_Error( 'crae_upstream_error', $msg, [ 'status' => $statusCode ] );
        }

        return is_array( $decoded ) ? $decoded : [];
    }

    public function getBaseUrl(): string {
        if ( defined( 'PSIVEY_BASE_URL' ) ) {
            $envUrl = (string) constant( 'PSIVEY_BASE_URL' );
            if ( '' !== $envUrl ) {
                return $envUrl;
            }
        }

        $settings = get_option( 'crae_settings', [] );
        return ! empty( $settings['psivey_base_url'] )
            ? esc_url_raw( (string) $settings['psivey_base_url'] )
            : 'https://api.psivey.com';
    }
}

