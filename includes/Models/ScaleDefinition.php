<?php
/**
 * Value Object representing a Clinical Scale or Subtest definition.
 *
 * @package ClicReport\AssessmentEngine\Models
 */

namespace ClicReport\AssessmentEngine\Models;

defined( 'ABSPATH' ) || exit;

final class ScaleDefinition {

    public function __construct(
        public readonly int $id,
        public readonly int $templateId,
        public readonly ?int $sectionId,
        public readonly ?int $parentScaleId,
        public readonly string $scaleKey,
        public readonly string $title,
        public readonly string $scaleType,
        public readonly bool $isComposite,
        public readonly ?float $cutoffThreshold,
        public readonly string $description,
        public readonly string $raterRestriction,
        public readonly string $ageRestriction,
        public readonly int $sortOrder
    ) {}

    public static function fromRow( array $row ): self {
        return new self(
            (int) ( $row['id'] ?? 0 ),
            (int) ( $row['template_id'] ?? 0 ),
            ! empty( $row['section_id'] ) ? (int) $row['section_id'] : null,
            ! empty( $row['parent_scale_id'] ) ? (int) $row['parent_scale_id'] : null,
            (string) ( $row['scale_key'] ?? '' ),
            (string) ( $row['title'] ?? '' ),
            (string) ( $row['scale_type'] ?? 'clinical' ),
            ! empty( $row['is_composite'] ),
            isset( $row['cutoff_threshold'] ) && '' !== $row['cutoff_threshold'] ? (float) $row['cutoff_threshold'] : null,
            (string) ( $row['description'] ?? '' ),
            (string) ( $row['rater_restriction'] ?? 'all' ),
            (string) ( $row['age_restriction'] ?? 'all' ),
            (int) ( $row['sort_order'] ?? 0 )
        );
    }
}

