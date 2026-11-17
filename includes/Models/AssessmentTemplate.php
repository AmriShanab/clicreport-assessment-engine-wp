<?php
/**
 * Immutable Value Object representing an Assessment Template record.
 *
 * @package ClicReport\AssessmentEngine\Models
 */

namespace ClicReport\AssessmentEngine\Models;

defined( 'ABSPATH' ) || exit;

final class AssessmentTemplate {

    public function __construct(
        public readonly int $id,
        public readonly string $templateSlug,
        public readonly string $templateName,
        public readonly ?string $publisher,
        public readonly string $category,
        public readonly string $archetype,
        public readonly string $defaultScoreSystem,
        public readonly ?string $introNarrative,
        public readonly array $config,
        public readonly bool $isActive
    ) {}

    public static function fromRow( array $row ): self {
        $config = [];
        if ( ! empty( $row['config_json'] ) ) {
            $decoded = json_decode( (string) $row['config_json'], true );
            if ( is_array( $decoded ) ) {
                $config = $decoded;
            }
        }

        return new self(
            (int) ( $row['id'] ?? 0 ),
            (string) ( $row['template_slug'] ?? '' ),
            (string) ( $row['template_name'] ?? '' ),
            isset( $row['publisher'] ) ? (string) $row['publisher'] : null,
            (string) ( $row['category'] ?? 'Social-Emotional' ),
            (string) ( $row['archetype'] ?? 't_score' ),
            (string) ( $row['default_score_system'] ?? 't_score' ),
            isset( $row['intro_narrative'] ) ? (string) $row['intro_narrative'] : null,
            $config,
            ! empty( $row['is_active'] )
        );
    }
}

