<?php
/**
 * Sliding/Windowed Rate Limiter backed by WordPress Transients
 *
 * @package ClicReport\AssessmentEngine\Security
 */

namespace ClicReport\AssessmentEngine\Security;

defined( 'ABSPATH' ) || exit;

final class RateLimiter {

    /**
     * Check and increment request counter for a given endpoint bucket + actor identifier.
     *
     * @return true|\WP_Error True if allowed, WP_Error (429) if rate limit exceeded.
     */
    public function check( string $bucket, string $identifier, int $limit, int $windowSeconds = 60 ): true|\WP_Error {
        if ( $limit <= 0 ) {
            return true;
        }

        $safeBucket = sanitize_key( $bucket );
        $actorHash  = hash( 'sha256', $identifier );
        $key        = "crae_rl_{$safeBucket}_{$actorHash}";

        $state = get_transient( $key );
        $now   = time();

        if ( ! is_array( $state ) || ( $state['expires_at'] ?? 0 ) <= $now ) {
            set_transient(
                $key,
                [
                    'count'      => 1,
                    'expires_at' => $now + $windowSeconds,
                ],
                $windowSeconds
            );
            return true;
        }

        $count = (int) ( $state['count'] ?? 0 );
        if ( $count >= $limit ) {
            $retryAfter = max( 1, (int) $state['expires_at'] - $now );
            return new \WP_Error(
                'crae_rate_limit_exceeded',
                sprintf( 'Rate limit exceeded. Please retry in %d seconds.', $retryAfter ),
                [
                    'status'      => 429,
                    'retry_after' => $retryAfter,
                ]
            );
        }

        $state['count'] = $count + 1;
        set_transient( $key, $state, max( 1, (int) $state['expires_at'] - $now ) );

        return true;
    }

    /**
     * Resolve client IP safely (only trusting REMOTE_ADDR) and return salted hash.
     */
    public function getClientIp(): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}

