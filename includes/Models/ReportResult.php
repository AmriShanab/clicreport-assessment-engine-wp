<?php
/**
 * Standardized Clinical Report Result DTO.
 *
 * @package ClicReport\AssessmentEngine\Models
 */

namespace ClicReport\AssessmentEngine\Models;

defined( 'ABSPATH' ) || exit;

final class ReportResult {

    public function __construct(
        public readonly string $status,
        public readonly string $requestId,
        public readonly string $templateName,
        public readonly string $archetype,
        public readonly string $examinee,
        public readonly array $pronouns,
        public readonly array $scoresEvaluated,
        public readonly string $narrativeHtml,
        public readonly float $executionTimeMs
    ) {}

    public function toArray(): array {
        return [
            'status'            => $this->status,
            'request_id'        => $this->requestId,
            'template_name'     => $this->templateName,
            'archetype'         => $this->archetype,
            'examinee'          => $this->examinee,
            'pronouns'          => $this->pronouns,
            'scores_evaluated'  => $this->scoresEvaluated,
            'narrative_html'    => $this->narrativeHtml,
            'execution_time_ms' => $this->executionTimeMs,
        ];
    }
}

