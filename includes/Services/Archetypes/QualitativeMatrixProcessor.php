<?php
/**
 * QualitativeMatrixProcessor (Archetype 5 Engine - 23 Templates)
 *
 * Powers Qualitative Decision Matrices, IDEA Special Education Eligibility Worksheets,
 * EBR Academic/Behavioral Strategy Checklists, FBA, Kennedy Axis V GAF, and Dunn-Rankin.
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;
use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

defined( 'ABSPATH' ) || exit;

final class QualitativeMatrixProcessor implements ArchetypeProcessorInterface {

    public function process(
        array $templateRow,
        array $sections,
        array $scales,
        array $inputData,
        NarrativeRuleRepository $narrativeRepo
    ): array {
        $templateId = (int) $templateRow['id'];
        $slug       = (string) $templateRow['template_slug'];

        $context = NarrativeInterpolator::buildExamineeContext(
            $inputData['first_name'] ?? ( $inputData['examinee_name'] ?? ( $inputData['examinee']['first_name'] ?? '' ) ),
            $inputData['gender'] ?? ( $inputData['examinee']['gender'] ?? 'male' )
        );

        $narrativeHtml   = '';
        $scoresEvaluated = [];

        if ( ! empty( $templateRow['intro_narrative'] ) ) {
            $intro          = NarrativeInterpolator::render( $templateRow['intro_narrative'], $context );
            $narrativeHtml .= "<p class='matrix-intro'>{$intro}</p>";
        }

        $allOptions         = $narrativeRepo->getDecisionOptions( $templateId );
        $optionsByCriterion = [];
        foreach ( $allOptions as $opt ) {
            $optionsByCriterion[ $opt['criterion_key'] ][ $opt['option_value'] ] = $opt['narrative_fragment'];
        }

        // Special Engine 1: Kennedy Axis V Global Assessment of Functioning
        if ( str_contains( $slug, 'kennedy-axis-v' ) ) {
            $gafInputs                      = $inputData['scores'] ?? $inputData;
            $gafResult                      = AssessmentScorer::computeKennedyGAF( $gafInputs );
            $scoresEvaluated['kennedy_gaf'] = $gafResult;

            $narrativeHtml .= "<p class='kennedy-gaf-score'><strong>Kennedy Axis V Global Assessment of Functioning (GAF) Equivalent Score:</strong> {$gafResult['gaf_score']}</p>";

            foreach ( $gafResult['domains_evaluated'] as $domainKey => $domainInfo ) {
                $bracketKey = $domainInfo['bracket'];
                $domAttr    = esc_attr( sanitize_key( (string) $domainKey ) );

                if ( isset( $optionsByCriterion[ $domainKey ][ $bracketKey ] ) ) {
                    $frag           = NarrativeInterpolator::render( $optionsByCriterion[ $domainKey ][ $bracketKey ], $context );
                    $narrativeHtml .= "<p class='kennedy-{$domAttr}'>{$frag}</p>";
                } else {
                    $title          = esc_html( ucwords( str_replace( '_', ' ', (string) $domainKey ) ) );
                    $nameEsc        = esc_html( $context['examinee_name'] );
                    $narrativeHtml .= "<p class='kennedy-{$domAttr}'><strong>{$title} (Score: {$domainInfo['score']}):</strong> {$nameEsc} obtained a domain rating of {$domainInfo['score']} (bracket " . esc_html( (string) $bracketKey ) . ').</p>';
                }
            }

            return [
                'examinee'         => $context['examinee_name'],
                'pronouns'         => $context,
                'scores_evaluated' => $scoresEvaluated,
                'narrative_html'   => $narrativeHtml,
            ];
        }

        // Special Engine 2: Rank Preference Inventories (Dunn-Rankin)
        $rankScales = array_filter( $scales, static fn( array $s ): bool => 'rank_preference' === $s['scale_type'] );
        if ( ! empty( $rankScales ) ) {
            $categoryInputs = [];
            foreach ( $rankScales as $rs ) {
                $key = $rs['scale_key'];
                $val = $inputData[ $key ] ?? ( $inputData['scores'][ $key ] ?? null );
                if ( null !== $val && '' !== $val ) {
                    $categoryInputs[ $rs['title'] ] = $val;
                }
            }

            if ( ! empty( $categoryInputs ) ) {
                $ranked                                = AssessmentScorer::rankPreferences( $categoryInputs );
                $scoresEvaluated['ranked_preferences'] = $ranked;

                $topPref    = $ranked[0];
                $secondPref = $ranked[1] ?? null;
                $lowestPref = end( $ranked );

                $nameEsc  = esc_html( $context['examinee_name'] );
                $possEsc  = esc_html( $context['possessive_name'] );
                $topCat   = esc_html( (string) $topPref['category'] );

                $rankPara  = "<p class='preference-hierarchy'><strong>Reinforcement &amp; Preference Hierarchy:</strong> ";
                $rankPara .= "{$nameEsc} completed the preference inventory to identify the most motivating classroom reinforcers. ";
                $rankPara .= "Results indicate that {$possEsc} strongest preference is for <strong>{$topCat}</strong> ({$topPref['score']}%), which ranked {$topPref['rank_ordinal']} overall.";

                if ( $secondPref ) {
                    $secCat    = esc_html( (string) $secondPref['category'] );
                    $rankPara .= ' ' . esc_html( $context['Possessive'] ) . " second highest preference was <strong>{$secCat}</strong> ({$secondPref['score']}%).";
                }
                if ( count( $ranked ) > 2 && $lowestPref ) {
                    $lowCat    = esc_html( (string) $lowestPref['category'] );
                    $rankPara .= " Conversely, <strong>{$lowCat}</strong> ({$lowestPref['score']}%) was rated as " . esc_html( $context['possessive'] ) . ' least preferred form of reinforcement.';
                }

                $rankPara      .= " Educators and support staff are encouraged to incorporate {$topCat}" . ( $secondPref ? ' and ' . esc_html( (string) $secondPref['category'] ) : '' ) . " into {$possEsc} behavioral and instructional support plan.</p>";
                $narrativeHtml .= $rankPara;
            }
        }

        // Process Qualitative Decision Matrix Options
        $criteriaFragments = [];
        foreach ( $optionsByCriterion as $criterionKey => $valueMap ) {
            $selectedVal = $inputData[ $criterionKey ] ?? ( $inputData['criteria'][ $criterionKey ] ?? ( $inputData['scores'][ $criterionKey ] ?? null ) );
            if ( null === $selectedVal || '' === $selectedVal ) {
                continue;
            }

            if ( ( true === $selectedVal || '1' === $selectedVal || 1 === $selectedVal || 'on' === $selectedVal ) && isset( $valueMap['checked'] ) ) {
                $selectedVal = 'checked';
            }

            $selectedList = is_array( $selectedVal ) ? $selectedVal : [ $selectedVal ];
            foreach ( $selectedList as $sel ) {
                $selStr = trim( (string) $sel );
                if ( isset( $valueMap[ $selStr ] ) ) {
                    $renderedFragment                    = NarrativeInterpolator::render( $valueMap[ $selStr ], $context );
                    $criteriaFragments[]                 = $renderedFragment;
                    $scoresEvaluated[ $criterionKey ][]  = [
                        'selected'  => $selStr,
                        'narrative' => $renderedFragment,
                    ];
                }
            }
        }

        if ( ! empty( $criteriaFragments ) ) {
            $narrativeHtml .= "<p class='qualitative-synthesis'>" . implode( ' ', $criteriaFragments ) . '</p>';
        }

        // Process Categorical Scales without explicit decision_options
        $categoricalScales = array_filter( $scales, static fn( array $s ): bool => 'rank_preference' !== $s['scale_type'] );
        foreach ( $categoricalScales as $scale ) {
            $key = (string) $scale['scale_key'];
            if ( isset( $optionsByCriterion[ $key ] ) ) {
                continue;
            }
            $val = $inputData[ $key ] ?? ( $inputData['scores'][ $key ] ?? null );
            if ( null === $val || '' === $val || false === $val || '0' === $val ) {
                continue;
            }

            $scaleContext = array_merge(
                $context,
                [
                    'scale_title' => $scale['title'],
                    'value'       => sanitize_text_field( (string) $val ),
                ]
            );
            $desc                    = NarrativeInterpolator::render( $scale['description'], $scaleContext );
            $keyAttr                 = esc_attr( $key );
            $narrativeHtml          .= "<p class='{$keyAttr}_p'><strong>" . esc_html( (string) $scale['title'] ) . ":</strong> {$desc}</p>";
            $scoresEvaluated[ $key ] = sanitize_text_field( (string) $val );
        }

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $narrativeHtml,
        ];
    }
}

