<?php
/**
 * Short-Lived HMAC-SHA256 JWT Token Service for Psivey AI & External Clients
 *
 * Supports issuance, signature verification, expiration, and per-user revocation.
 *
 * @package ClicReport\AssessmentEngine\Authentication
 */

namespace ClicReport\AssessmentEngine\Authentication;

defined( 'ABSPATH' ) || exit;

final class PsiveyTokenService {

    /**
     * Issue a signed JWT token for an authenticated WordPress user.
     *
     * @return array{token: string, expires_in: int, expires_at: int}
     * @throws \RuntimeException If no valid WordPress user is provided.
     */
    public function issueForUser( \WP_User $user ): array {
        if ( ! $user->exists() || 0 === $user->ID ) {
            throw new \RuntimeException( 'Cannot issue token for unauthenticated user.' );
        }

        $settings = get_option( 'crae_settings', [] );
        $ttl      = max( 60, min( 86400, (int) ( $settings['psivey_jwt_ttl_seconds'] ?? 900 ) ) );
        $now      = time();
        $exp      = $now + $ttl;

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = [
            'iss'        => home_url(),
            'sub'        => (int) $user->ID,
            'user_id'    => (int) $user->ID,
            'user_login' => $user->user_login,
            'email'      => $user->user_email,
            'jti'        => bin2hex( random_bytes( 12 ) ),
            'iat'        => $now,
            'exp'        => $exp,
        ];

        $segments      = [
            $this->base64UrlEncode( (string) wp_json_encode( $header ) ),
            $this->base64UrlEncode( (string) wp_json_encode( $payload ) ),
        ];
        $signingInput  = implode( '.', $segments );
        $signature     = hash_hmac( 'sha256', $signingInput, $this->getSigningSecret(), true );
        $segments[]    = $this->base64UrlEncode( $signature );

        return [
            'token'      => implode( '.', $segments ),
            'expires_in' => $ttl,
            'expires_at' => $exp,
        ];
    }

    /**
     * Validate a JWT token string and verify expiration + revocation status.
     *
     * @return array<string, mixed>|\WP_Error Decoded payload or WP_Error on failure.
     */
    public function verifyToken( string $jwt ): array|\WP_Error {
        $parts = explode( '.', trim( $jwt ) );
        if ( 3 !== count( $parts ) ) {
            return new \WP_Error( 'crae_invalid_token', 'Malformed authorization token.', [ 'status' => 401 ] );
        }

        [ $encodedHeader, $encodedPayload, $encodedSig ] = $parts;
        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $expectedSig  = $this->base64UrlEncode( hash_hmac( 'sha256', $signingInput, $this->getSigningSecret(), true ) );

        if ( ! hash_equals( $expectedSig, $encodedSig ) ) {
            return new \WP_Error( 'crae_invalid_signature', 'Invalid token signature.', [ 'status' => 401 ] );
        }

        $payload = json_decode( $this->base64UrlDecode( $encodedPayload ), true );
        if ( ! is_array( $payload ) || empty( $payload['sub'] ) ) {
            return new \WP_Error( 'crae_invalid_claims', 'Malformed token claims.', [ 'status' => 401 ] );
        }

        $now = time();
        if ( empty( $payload['exp'] ) || (int) $payload['exp'] < $now ) {
            return new \WP_Error( 'crae_token_expired', 'Authorization token has expired.', [ 'status' => 401 ] );
        }

        $userId        = (int) $payload['sub'];
        $revokedBefore = (int) get_user_meta( $userId, 'crae_token_revoked_before', true );
        if ( $revokedBefore > 0 && (int) ( $payload['iat'] ?? 0 ) <= $revokedBefore ) {
            return new \WP_Error( 'crae_token_revoked', 'Authorization token has been revoked.', [ 'status' => 401 ] );
        }

        return $payload;
    }

    /**
     * Validate and consume a one-time Psivey-Token.
     *
     * Once consumed, this token cannot be reused for report generation or data access.
     *
     * @param string $jwt            The Psivey JWT token string.
     * @param int    $expectedUserId Expected user ID (0 to skip user match check).
     * @return array<string, mixed>|\WP_Error Decoded payload or WP_Error on failure.
     */
    public function verifyAndConsumeToken( string $jwt, int $expectedUserId = 0 ): array|\WP_Error {
        $jwt = trim( $jwt );
        if ( '' === $jwt ) {
            return new \WP_Error(
                'crae_missing_psivey_token',
                __( 'A valid one-time Psivey-Token is required to access or generate assessment reports.', 'clicreport-assessment-engine' ),
                [ 'status' => 401 ]
            );
        }

        $payload = $this->verifyToken( $jwt );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        if ( $expectedUserId > 0 && (int) ( $payload['sub'] ?? 0 ) !== $expectedUserId ) {
            return new \WP_Error(
                'crae_token_user_mismatch',
                __( 'Psivey-Token does not belong to the current authenticated user session.', 'clicreport-assessment-engine' ),
                [ 'status' => 403 ]
            );
        }

        $jti = (string) ( $payload['jti'] ?? '' );
        if ( '' === $jti ) {
            return new \WP_Error(
                'crae_invalid_token_jti',
                __( 'Psivey-Token is missing required unique transaction identifier.', 'clicreport-assessment-engine' ),
                [ 'status' => 401 ]
            );
        }

        $consumedKey = 'crae_consumed_psivey_' . $jti;
        if ( get_transient( $consumedKey ) ) {
            return new \WP_Error(
                'crae_token_already_consumed',
                __( 'This one-time Psivey-Token has already been consumed. Please generate a fresh token.', 'clicreport-assessment-engine' ),
                [ 'status' => 403 ]
            );
        }

        // Mark consumed in transients until token expires
        $remainingTtl = max( 60, (int) ( $payload['exp'] ?? ( time() + 900 ) ) - time() );
        set_transient( $consumedKey, time(), $remainingTtl );

        return $payload;
    }

    /**
     * Revoke all previously issued JWT tokens for a WordPress user.
     */
    public function revokeUserTokens( int $userId ): void {
        if ( $userId > 0 ) {
            update_user_meta( $userId, 'crae_token_revoked_before', time() );
        }
    }

    private function getSigningSecret(): string {
        if ( defined( 'PSIVEY_JWT_SECRET' ) ) {
            $envSecret = (string) constant( 'PSIVEY_JWT_SECRET' );
            if ( '' !== $envSecret ) {
                return $envSecret;
            }
        }

        $settings = get_option( 'crae_settings', [] );
        if ( ! empty( $settings['psivey_jwt_secret'] ) ) {
            return (string) $settings['psivey_jwt_secret'];
        }

        return wp_salt( 'auth' );
    }

    private function base64UrlEncode( string $data ): string {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    private function base64UrlDecode( string $data ): string {
        $remainder = strlen( $data ) % 4;
        if ( $remainder > 0 ) {
            $data .= str_repeat( '=', 4 - $remainder );
        }
        return (string) base64_decode( strtr( $data, '-_', '+/' ) );
    }
}

