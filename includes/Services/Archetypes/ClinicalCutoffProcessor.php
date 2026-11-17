<?php
/**
 * ClinicalCutoffProcessor (Archetype 3 Engine - 6 Templates)
 *
 * Powers Clinical Diagnostic Cutoff assessments: ADI-R, ADOS-2 (Mod 1-3), SCQ, Vanderbilt-2.
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Services\Narrative\InformantManager;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;
use ClicReport\AssessmentEngine\Services\Narrative\ReportGrammar;
use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

defined( 'ABSPATH' ) || exit;

final class ClinicalCutoffProcessor implements ArchetypeProcessorInterface {

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

        $informants                    = InformantManager::extractInformants( $inputData );
        $context['informant_name']     = $informants[0]['name'];
        $context['informant_relation'] = $informants[0]['relation'];

        $narrativeHtml        = '';
        $scoresEvaluated      = [];
        $indicativeDomains    = [];
        $nonIndicativeDomains = [];

        if ( ! empty( $templateRow['intro_narrative'] ) ) {
            $intro          = NarrativeInterpolator::render( $templateRow['intro_narrative'], $context );
            $narrativeHtml .= "<p class='cutoff-intro'>{$intro}</p>";
        }

        if ( ! empty( $inputData['endorsed_items'] ) && is_array( $inputData['endorsed_items'] ) ) {
            $endorsedCount = count( $inputData['endorsed_items'] );
            if ( ! isset( $inputData['total_score'] ) && ! isset( $inputData['scores']['total_score'] ) ) {
                $inputData['scores']['total_score'] = $endorsedCount;
            }
        }

        foreach ( $scales as $scale ) {
            $scaleKey = (string) $scale['scale_key'];
            $rawVal   = $inputData[ $scaleKey ] ?? ( $inputData['scores'][ $scaleKey ] ?? null );

            if ( null === $rawVal || '' === $rawVal ) {
                continue;
            }

            $cutoff                       = (float) ( $scale['cutoff_threshold'] ?? 10.0 );
            $scored                       = AssessmentScorer::classifyClinicalCutoff( $rawVal, $cutoff );
            $scoresEvaluated[ $scaleKey ] = array_merge( [ 'title' => $scale['title'] ], $scored );

            if ( $scored['is_elevated'] ) {
                $indicativeDomains[] = $scale['title'];
            } else {
                $nonIndicativeDomains[] = $scale['title'];
            }

            $scaleContext = array_merge(
                $context,
                [
                    'scale_title'     => $scale['title'],
                    'score'           => $scored['score'],
                    'cutoff'          => $scored['cutoff'],
                    'comparison_word' => $scored['comparison_word'],
                    'indicative_text' => $scored['indicative_text'],
                ]
            );

            $safeKey  = esc_attr( $scaleKey );
            $titleEsc = esc_html( (string) $scale['title'] );
            $nameEsc  = esc_html( $context['examinee_name'] );
            $compWord = esc_html( (string) $scored['comparison_word'] );
            $indText  = esc_html( (string) $scored['indicative_text'] );

            $para  = "<p class='{$safeKey}_p'><strong>{$titleEsc}:</strong> ";
            $para .= NarrativeInterpolator::render( $scale['description'], $scaleContext ) . ' ';
            $para .= "{$nameEsc} received a score of {$scored['score']} (clinical cutoff = {$scored['cutoff']}), which is <strong>{$compWord}</strong> the diagnostic cutoff and is <strong>{$indText}</strong> of clinically significant symptomatology in this domain.";

            if ( $scored['is_elevated'] && ! empty( $scale['elevated_template'] ) ) {
                $para .= ' ' . NarrativeInterpolator::render( $scale['elevated_template'], $scaleContext );
            } elseif ( ! $scored['is_elevated'] && ! empty( $scale['average_template'] ) ) {
                $para .= ' ' . NarrativeInterpolator::render( $scale['average_template'], $scaleContext );
            }

            $para          .= '</p>';
            $narrativeHtml .= $para;
        }

        if ( ! empty( $inputData['endorsed_items'] ) && is_array( $inputData['endorsed_items'] ) ) {
            $itemsHtml = "<ul class='scq-endorsed-items'>";
            foreach ( $inputData['endorsed_items'] as $itemText ) {
                $renderedItem = NarrativeInterpolator::render( sanitize_text_field( (string) $itemText ), $context );
                $itemsHtml   .= '<li>' . esc_html( $renderedItem ) . '</li>';
            }
            $itemsHtml     .= '</ul>';
            $narrativeHtml .= $itemsHtml;
        }

        $totalEvaluated  = count( $scoresEvaluated );
        $totalIndicative = count( $indicativeDomains );
        $possEsc         = esc_html( $context['possessive_name'] );

        if ( $totalEvaluated > 0 ) {
            if ( $totalIndicative === $totalEvaluated ) {
                $domainList     = esc_html( ReportGrammar::joinList( $indicativeDomains, 'and' ) );
                $narrativeHtml .= "<p class='diagnostic-summary'><strong>Diagnostic Impression:</strong> {$possEsc} scores met or exceeded the clinical cutoff across all evaluated domains ({$domainList}). These results are strongly consistent with the diagnostic criteria evaluated by this measure and warrant comprehensive clinical consideration.</p>";
            } elseif ( $totalIndicative > 0 ) {
                $indList        = esc_html( ReportGrammar::joinList( $indicativeDomains, 'and' ) );
                $nonIndList     = esc_html( ReportGrammar::joinList( $nonIndicativeDomains, 'and' ) );
                $narrativeHtml .= "<p class='diagnostic-summary'><strong>Diagnostic Impression:</strong> {$possEsc} scores met or exceeded the clinical cutoff in {$indList}, while remaining below the threshold in {$nonIndList}. Results should be integrated with clinical observations and multi-disciplinary evaluation data.</p>";
            } else {
                $narrativeHtml .= "<p class='diagnostic-summary'><strong>Diagnostic Impression:</strong> {$possEsc} scores fell below the clinical diagnostic cutoff across all evaluated domains, indicating that observed behaviors do not currently meet threshold criteria on this measure.</p>";
            }
        }

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $narrativeHtml,
        ];
    }
}

