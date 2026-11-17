<?php
/**
 * StaticReferenceProcessor (Archetype 4 Engine - 46 Templates)
 *
 * Handles static reference charts, score descriptor cheat sheets, graph visualizers,
 * cover pages, stopwatch timers, and non-assessment utility pages.
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;

defined( 'ABSPATH' ) || exit;

final class StaticReferenceProcessor implements ArchetypeProcessorInterface {

    public function process(
        array $templateRow,
        array $sections,
        array $scales,
        array $inputData,
        NarrativeRuleRepository $narrativeRepo
    ): array {
        $context = NarrativeInterpolator::buildExamineeContext(
            $inputData['first_name'] ?? ( $inputData['examinee']['first_name'] ?? '' ),
            $inputData['gender'] ?? ( $inputData['examinee']['gender'] ?? 'male' )
        );

        $title = esc_html( (string) $templateRow['template_name'] );
        $intro = ! empty( $templateRow['intro_narrative'] )
            ? NarrativeInterpolator::render( $templateRow['intro_narrative'], $context )
            : "This template ({$title}) is a static normative reference chart or utility interface and does not require psychometric score computation.";

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => [
                'template_type' => 'static_reference_or_utility',
                'category'      => $templateRow['category'] ?? 'Reference',
            ],
            'narrative_html'   => "<p class='static-reference-notice'><strong>{$title}:</strong> {$intro}</p>",
        ];
    }
}

