<?php
/**
 * Recursive Input Sanitizer & Field Whitelister
 *
 * @package ClicReport\AssessmentEngine\Security
 */

namespace ClicReport\AssessmentEngine\Security;

defined( 'ABSPATH' ) || exit;

final class InputSanitizer {

    private const ALLOWED_PRONOUNS = [
        'male', 'female', 'neutral', 'non-binary',
        'he/him', 'she/her', 'they/them', 'ze/hir', 'ze/zir', 'xey/xem',
        'm', 'f', 'boy', 'girl', 'man', 'woman', 'they', 'other',
    ];

    /**
     * Recursively sanitize an arbitrary assessment payload while preserving numeric types.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeArray( array $input, int $depth = 0 ): array {
        if ( $depth > 6 ) {
            return [];
        }

        $clean = [];
        foreach ( $input as $rawKey => $value ) {
            $key = is_int( $rawKey )
                ? $rawKey
                : preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $rawKey );

            if ( '' === $key ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $clean[ $key ] = $this->sanitizeArray( $value, $depth + 1 );
                continue;
            }

            if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
                $clean[ $key ] = $value;
                continue;
            }

            if ( null === $value ) {
                $clean[ $key ] = null;
                continue;
            }

            $str = sanitize_text_field( wp_unslash( (string) $value ) );
            $clean[ $key ] = is_numeric( $str ) ? ( str_contains( $str, '.' ) ? (float) $str : (int) $str ) : $str;
        }

        return $clean;
    }

    /**
     * Normalize legacy and canonical template slugs safely while preserving spaces,
     * parentheses, commas, and dots used by legacy filenames (blocking SQL/HTML punctuation).
     */
    public function sanitizeTemplateSlug( string $rawSlug ): string {
        $slug = strtolower( trim( sanitize_text_field( wp_unslash( $rawSlug ) ) ) );
        if ( str_ends_with( $slug, '.php' ) ) {
            $slug = substr( $slug, 0, -4 );
        }

        return trim( (string) preg_replace( '/[^a-z0-9 _\-\.\(\),]/', '', $slug ) );
    }

    /**
     * Enforce strict gender/pronoun whitelist.
     */
    public function sanitizePronoun( string $rawGender ): string {
        $normalized = strtolower( trim( sanitize_text_field( wp_unslash( $rawGender ) ) ) );
        return in_array( $normalized, self::ALLOWED_PRONOUNS, true ) ? $normalized : 'male';
    }
}

