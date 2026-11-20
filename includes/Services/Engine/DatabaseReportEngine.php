<?php

/**
 * Universal WordPress Database-Driven Assessment Report Engine (v1.0)
 *
 * Orchestrates all 201 templates across 5 Archetypes using $wpdb Repositories,
 * WordPress wp_kses() sanitization, and non-PII HIPAA/FERPA audit logging.
 *
 * @package ClicReport\AssessmentEngine\Services\Engine
 */

namespace ClicReport\AssessmentEngine\Services\Engine;

use ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository;
use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;

defined('ABSPATH') || exit;

final class DatabaseReportEngine
{

    public function __construct(
        private TemplateRepository $templateRepo,
        private ScaleRepository $scaleRepo,
        private NarrativeRuleRepository $narrativeRepo,
        private AuditLogRepository $auditRepo
    ) {}

    /**
     * Generate a clinical report for any of the 201 assessment templates.
     *
     * @param array<string, mixed> $data   Validated/sanitized assessment payload.
     * @param int                  $userId Authenticated WordPress user ID.
     * @param string               $rawIp  Client IP (hashed via HMAC before audit logging).
     * @return array<string, mixed>
     * @throws \InvalidArgumentException If the requested template slug does not exist.
     */
    public function generate(array $data, int $userId = 0, string $rawIp = ''): array
    {
        $startTime = microtime(true);
        $requestId = substr(bin2hex(random_bytes(8)), 0, 16);

        $templateSlug = trim(
            (string) ($data['template_slug'] ?? ($data['template_name'] ?? ($data['template_id'] ?? ($data['assessment_type'] ?? 'report-basc-3-social-emotional'))))
        );
        if (str_ends_with(strtolower($templateSlug), '.php')) {
            $templateSlug = substr($templateSlug, 0, -4);
        }
        $templateSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9 _\-\.\(\),]/', '', sanitize_text_field($templateSlug))));

        $template = $this->templateRepo->findBySlug($templateSlug);
        if (null === $template) {
            throw new \InvalidArgumentException(
                sprintf("Assessment template '%s' was not found.", $templateSlug)
            );
        }

        $templateId = (int) $template['id'];
        $archetype  = (string) ($template['archetype'] ?: ArchetypeFactory::inferFromScoreSystem((string) ($template['default_score_system'] ?? 't_score')));

        $sections = $this->templateRepo->getSections($templateId);
        $scales   = $this->scaleRepo->getByTemplate($templateId);

        if (! empty($scales)) {
            $scaleIds     = array_map(static fn(array $s): int => (int) $s['id'], $scales);
            $rulesByScale = $this->narrativeRepo->getRulesIndexedByScale($scaleIds);

            foreach ($scales as &$scale) {
                $sid                       = (int) $scale['id'];
                $scale['elevated_template'] = $rulesByScale[$sid]['elevated'] ?? null;
                $scale['average_template']  = $rulesByScale[$sid]['average'] ?? null;
            }
            unset($scale);
        }

        $processor = ArchetypeFactory::create($archetype);
        $processed = $processor->process($template, $sections, $scales, $data, $this->narrativeRepo);

        $sanitizedHtml = NarrativeInterpolator::sanitizeClinicalHtml((string) ($processed['narrative_html'] ?? ''));

        $allowPartial = ! empty($data['allow_partial']) && ('1' === $data['allow_partial'] || 1 === $data['allow_partial'] || 'true' === $data['allow_partial'] || true === $data['allow_partial']);
        if (! $allowPartial && preg_match('/\b(N\/A|NA)\b/i', $sanitizedHtml)) {
            throw new \InvalidArgumentException(
                'Incomplete assessment data: One or more required scores are missing. Please enter all scores before generating the narrative.'
            );
        }

        $elapsedMs     = (microtime(true) - $startTime) * 1000;

        $this->auditRepo->record(
            $requestId,
            $userId,
            $templateSlug,
            $archetype,
            $elapsedMs,
            'success',
            $rawIp
        );

        $response = [
            'status'            => 'success',
            'request_id'        => $requestId,
            'engine'            => 'WordPress DatabaseReportEngine (' . basename(str_replace('\\', '/', $processor::class)) . ')',
            'archetype'         => $archetype,
            'template_name'     => $templateSlug,
            'examinee'          => $processed['examinee'],
            'pronouns'          => $processed['pronouns'],
            'scores_evaluated'  => $processed['scores_evaluated'],
            'narrative_html'    => $sanitizedHtml,
            'execution_time_ms' => round($elapsedMs, 2),
        ];

        if (! empty($processed['narrative_summary_html'])) {
            $response['narrative_summary_html'] = NarrativeInterpolator::sanitizeClinicalHtml((string) $processed['narrative_summary_html']);
        }

        if (! empty($processed['performance_summary_html'])) {
            $response['performance_summary_html'] = NarrativeInterpolator::sanitizeClinicalHtml((string) $processed['performance_summary_html']);
        }

        if (! empty($processed['bell_curve'])) {
            $response['bell_curve'] = $processed['bell_curve'];
        }

        return $response;
    }
}
