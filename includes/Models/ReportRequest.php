<?php
/**
 * Standardized Report Request DTO.
 *
 * @package ClicReport\AssessmentEngine\Models
 */

namespace ClicReport\AssessmentEngine\Models;

defined( 'ABSPATH' ) || exit;

final class ReportRequest {

    public function __construct(
        public readonly string $templateSlug,
        public readonly array $payload,
        public readonly int $userId = 0,
        public readonly string $clientIp = ''
    ) {}
}

