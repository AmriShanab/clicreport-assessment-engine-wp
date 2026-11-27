<?php
/**
 * NormativeTScoreProcessor (Archetype 1 Engine - 30 Templates)
 *
 * Powers Normative T-Score (Mean = 50, SD = 10) assessments across 5 clinical profiles:
 * 'basc_standard', 'srs2_severity', 'eddt_clinical', 'masc2_anxiety', 'spm2_sensory'.
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Services\Narrative\InformantManager;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;
use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

defined( 'ABSPATH' ) || exit;

final class NormativeTScoreProcessor implements ArchetypeProcessorInterface {

    public function process(
        array $templateRow,
        array $sections,
        array $scales,
        array $inputData,
        NarrativeRuleRepository $narrativeRepo
    ): array {
        $templateId    = (int) $templateRow['id'];
        $config        = ! empty( $templateRow['config_json'] ) ? ( json_decode( (string) $templateRow['config_json'], true ) ?: [] ) : [];
        $tScoreProfile = $config['t_score_profile'] ?? $this->inferProfileFromSlug( (string) $templateRow['template_slug'] );

        $context = NarrativeInterpolator::buildExamineeContext(
            $inputData['first_name'] ?? ( $inputData['examinee']['first_name'] ?? '' ),
            $inputData['gender'] ?? ( $inputData['examinee']['gender'] ?? 'male' )
        );

        $informants        = InformantManager::extractInformants( $inputData );
        $activeRatersCount = InformantManager::resolveActiveRaterCount( $inputData );

        $slug = (string) ( $templateRow['template_slug'] ?? '' );
        if ( 'report-basc-3-social-emotional' === $slug ) {
            return $this->processBasc3SocialEmotional(
                $templateRow,
                $inputData,
                $context,
                $informants,
                $activeRatersCount,
                $tScoreProfile
            );
        }

        if ( 'report-basc-3-self-report' === $slug ) {
            return $this->processBasc3SelfReport(
                $templateRow,
                $inputData,
                $context,
                $scales
            );
        }

        if ( 'report-brief-2' === $slug ) {
            return $this->processBrief2(
                $templateRow,
                $inputData,
                $context,
                $informants,
                $activeRatersCount,
                $tScoreProfile
            );
        }

        $context['raters_intro'] = InformantManager::buildRatersIntro( $informants, $activeRatersCount, $context['possessive'] );

        $isPreschool  = $this->isFlagActive( $inputData['pre_school_h'] ?? null );
        $hideSocial   = $this->isFlagActive( $inputData['Social'] ?? null );
        $hideAdaptive = $this->isFlagActive( $inputData['adaptive'] ?? null );

        $scalesBySection = [];
        foreach ( $scales as $scale ) {
            $secId                       = (int) ( $scale['section_id'] ?? 0 );
            $scalesBySection[ $secId ][] = $scale;
        }

        if ( empty( $sections ) && ! empty( $scales ) ) {
            $sections = [
                [
                    'id'              => 0,
                    'section_key'     => 'social_emotional',
                    'title'           => $templateRow['template_name'],
                    'intro_narrative' => null,
                    'toggle_key'      => null,
                ],
            ];
        }

        $narrativeHtml   = '';
        $scoresEvaluated = [];

        foreach ( $sections as $sec ) {
            if ( 'Social' === $sec['toggle_key'] && $hideSocial ) {
                continue;
            }
            if ( 'adaptive' === $sec['toggle_key'] && $hideAdaptive ) {
                continue;
            }

            $secKey         = esc_attr( (string) $sec['section_key'] );
            $narrativeHtml .= "<p class='{$secKey}_n'><strong>" . esc_html( (string) $sec['title'] ) . '</strong></p>';

            if ( ! empty( $sec['intro_narrative'] ) ) {
                $introText      = NarrativeInterpolator::render( $sec['intro_narrative'], $context );
                $narrativeHtml .= "<p class='{$secKey}-body_n'>{$introText}</p>";
            } elseif ( 'social_emotional' === $secKey && ! empty( $templateRow['intro_narrative'] ) ) {
                $introText      = NarrativeInterpolator::render( $templateRow['intro_narrative'], $context );
                $narrativeHtml .= "<p class='Social-body_n'>{$introText}</p>";
            }

            $secScales = $scalesBySection[ (int) $sec['id'] ] ?? ( $scalesBySection[0] ?? [] );
            foreach ( $secScales as $scale ) {
                if ( $isPreschool && 'school_age_only' === $scale['age_restriction'] ) {
                    continue;
                }
                if ( ! $isPreschool && 'preschool_only' === $scale['age_restriction'] ) {
                    continue;
                }

                $scaleKey  = (string) $scale['scale_key'];
                $scaleType = (string) $scale['scale_type'];

                $raterScores = [];
                for ( $r = 1; $r <= max( 3, $activeRatersCount ); $r++ ) {
                    $rawVal = $this->extractRaterScoreValue( $inputData, $scaleKey, $r );
                    $raterScores[ $r ] = AssessmentScorer::classifyTScoreByProfile( $rawVal, $scaleType, $tScoreProfile );
                }

                if ( null === $raterScores[1]['score'] && empty( $inputData[ "{$scaleKey}_1" ] ) && ! isset( $inputData['informant_one'] ) ) {
                    continue;
                }

                $scoresEvaluated[ $scaleKey ] = [
                    'title'      => $scale['title'],
                    'scale_type' => $scaleType,
                    'profile'    => $tScoreProfile,
                    'raters'     => $raterScores,
                ];

                if ( 'teacher_only' === $scale['rater_restriction'] ) {
                    $narrativeHtml .= InformantManager::buildTeacherRestrictedParagraph(
                        $scale,
                        $raterScores,
                        $informants,
                        $activeRatersCount,
                        $context['possessive_name']
                    );
                    continue;
                }

                $isComp    = (bool) $scale['is_composite'];
                $tag       = $isComp ? 'b' : 'em';
                $safeKey   = esc_attr( $scaleKey );
                $pClass    = $isComp ? "{$safeKey}_p m_bt Social-body_n" : "{$safeKey}_p p_0 Social-body_n";
                $titleEsc  = esc_html( (string) $scale['title'] );
                $scaleDesc = NarrativeInterpolator::render( $scale['description'], $context );
                $agreement = InformantManager::buildMultiRaterAgreement( $raterScores, $informants, $activeRatersCount );

                $isAnyElevated = false;
                for ( $r = 1; $r <= $activeRatersCount; $r++ ) {
                    if ( ! empty( $raterScores[ $r ]['is_elevated'] ) ) {
                        $isAnyElevated = true;
                        break;
                    }
                }

                $possNameEsc = esc_html( $context['possessive_name'] );
                $rawRangeEsc = esc_html( (string) ( $raterScores[1]['raw_range'] ?? 'N/A' ) );

                $line  = "<p class='{$pClass}'><{$tag}>{$titleEsc}:</{$tag}> {$scaleDesc} {$possNameEsc} ";
                $line .= $isComp ? "overall level of {$titleEsc} was rated to be in the " : 'received ratings within the ';
                $line .= "<span class='capitalize'>{$rawRangeEsc}</span> range {$agreement}";

                if ( $isAnyElevated && ! empty( $scale['elevated_template'] ) ) {
                    $scaleContext   = array_merge(
                        $context,
                        [
                            'range'      => $raterScores[1]['range'],
                            'score'      => $raterScores[1]['score'],
                            'percentile' => $raterScores[1]['percentile'],
                        ]
                    );
                    $line          .= ' ' . NarrativeInterpolator::render( $scale['elevated_template'], $scaleContext );
                }

                $line          .= '</p>';
                $narrativeHtml .= $line;
            }
        }

        if ( empty( $scales ) ) {
            $scoreInputs = [];
            if ( ! empty( $inputData['scores'] ) && is_array( $inputData['scores'] ) ) {
                $scoreInputs = $inputData['scores'];
            } else {
                foreach ( $inputData as $k => $v ) {
                    if ( is_numeric( $v ) && ! in_array( $k, [ 'active_raters', 'user_id', 'nonce', 'timestamp' ], true ) ) {
                        $cleanKey = preg_replace( '/_[0-9]+$/', '', $k );
                        $scoreInputs[ $cleanKey ] = (float) $v;
                    }
                }
            }

            if ( ! empty( $scoreInputs ) ) {
                if ( ! empty( $templateRow['intro_narrative'] ) ) {
                    $narrativeHtml .= "<p class='Social-body_n'>" . NarrativeInterpolator::render( $templateRow['intro_narrative'], $context ) . '</p>';
                }
                foreach ( $scoreInputs as $key => $val ) {
                    $scored = AssessmentScorer::classifyTScoreByProfile( $val, 'clinical', $tScoreProfile );
                    if ( null === $scored['score'] ) {
                        continue;
                    }
                    $title                     = ucwords( str_replace( [ '_', '-' ], ' ', sanitize_text_field( (string) $key ) ) );
                    $scoresEvaluated[ $key ]   = array_merge( [ 'title' => $title, 'profile' => $tScoreProfile ], $scored );
                    $keyAttr                   = esc_attr( sanitize_key( (string) $key ) );
                    $narrativeHtml            .= "<p class='{$keyAttr}_p'><strong>" . esc_html( $title ) . ':</strong> ' . esc_html( $context['examinee_name'] ) . " earned a T-score of {$scored['score']} ({$scored['percentile_ordinal']} percentile), which falls within the <strong>" . esc_html( $scored['range'] ) . '</strong> range.</p>';
                }
            }
        }

        if ( ! $hideSocial && ! empty( $narrativeRepo->getValidityRules( $templateId ) ) ) {
            $validityHtml   = $this->buildValiditySection( $inputData, $context['possessive_name'], $informants, $activeRatersCount );
            $narrativeHtml .= "<p class='ch1 Social-body_n'>The Rating Scales assess a rater's response pattern to determine how results should be interpreted.{$validityHtml}</p>";
        }

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $narrativeHtml,
        ];
    }

    private function inferProfileFromSlug( string $slug ): string {
        if ( str_contains( $slug, 'srs-2' ) || str_contains( $slug, 'srs2' ) ) { return 'srs2_severity'; }
        if ( str_contains( $slug, 'eddt' ) ) { return 'eddt_clinical'; }
        if ( str_contains( $slug, 'masc-2' ) || str_contains( $slug, 'masc2' ) ) { return 'masc2_anxiety'; }
        if ( str_contains( $slug, 'spm' ) ) { return 'spm2_sensory'; }
        return 'basc_standard';
    }

    private function buildValiditySection( array $data, string $possName, array $informants, int $raterCount ): string {
        $f = [ $data['f_index'] ?? 'acceptable', $data['Response_Pattern'] ?? 'acceptable', $data['Consistency'] ?? 'acceptable' ];
        $o = [ $data['f_index1'] ?? 'acceptable', $data['Response_Pattern1'] ?? 'acceptable', $data['Consistency1'] ?? 'acceptable' ];

        $html = ' ' . $this->evaluateValiditySingle( $f, $informants[0]['name'], $possName, false );

        if ( $raterCount >= 2 ) {
            $html .= ' ' . $this->evaluateValiditySingle( $o, $informants[1]['name'], $possName, true );
            if ( 'acceptable' !== $o[1] || 'acceptable' !== $f[1] ) {
                $html .= ' The response pattern index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N).';
            }
            if ( 'acceptable' !== $o[2] || 'acceptable' !== $f[2] ) {
                $html .= ' The consistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way.';
            }
        }

        return $html;
    }

    private function evaluateValiditySingle( array $indexes, string $rater, string $possName, bool $isSecond ): string {
        $acc = 0;
        foreach ( $indexes as $v ) {
            if ( 'acceptable' === $v ) {
                $acc++;
            }
        }
        $prefix   = $isSecond ? 'Additionally, ' : '';
        $raterEsc = esc_html( $rater );
        $possEsc  = esc_html( $possName );

        if ( 3 === $acc ) {
            return "{$prefix}<span class='capitalize'>{$raterEsc}'s</span> rating consistency, response pattern, and F-Index were considered to be acceptable, indicating that these ratings are likely a valid representation of {$possEsc} current behavior/social-emotional functioning.";
        }
        return "{$prefix}<span class='capitalize'>{$raterEsc}'s</span> ratings indicated caution, and should be interpreted accordingly.";
    }

    private function isFlagActive( mixed $val ): bool {
        return ! empty( $val ) && ( '1' === $val || 1 === $val || 'true' === $val || true === $val || 'on' === $val );
    }

    /**
     * Specialized processor for BASC-3 Social / Emotional & Adaptive Functioning (100% Live Site parity)
     */
    private function processBasc3SocialEmotional(
        array $templateRow,
        array $inputData,
        array $context,
        array $informants,
        int $activeRatersCount,
        string $tScoreProfile
    ): array {
        $name     = ! empty( $context['name'] ) ? esc_html( $context['name'] ) : 'the examinee';
        $possName = ! empty( $context['possessive_name'] ) ? esc_html( $context['possessive_name'] ) : "the examinee's";
        $his_her  = ! empty( $context['possessive'] ) ? esc_html( $context['possessive'] ) : 'his';

        $inf_one = esc_html( $informants[0]['name'] ?? 'the rater one' );
        $rel_one = esc_html( $informants[0]['relation'] ?? 'mother' );
        $isTeacher1 = ! empty( $informants[0]['is_teacher'] );

        $inf_two = esc_html( $informants[1]['name'] ?? 'the rater two' );
        $rel_two = esc_html( $informants[1]['relation'] ?? 'general education teacher' );
        $isTeacher2 = ! empty( $informants[1]['is_teacher'] );

        $inf_thr = esc_html( $informants[2]['name'] ?? 'the rater three' );
        $rel_thr = esc_html( $informants[2]['relation'] ?? 'general education teacher' );
        $isTeacher3 = ! empty( $informants[2]['is_teacher'] );

        $isPreschool  = $this->isFlagActive( $inputData['pre_school_h'] ?? null );
        $hideSocial   = $this->isFlagActive( $inputData['Social'] ?? null );
        $hideAdaptive = $this->isFlagActive( $inputData['adaptive'] ?? null );

        $scaleMeta = [
            'externalizing_problems'   => [ 'title' => 'Externalizing Problems', 'type' => 'clinical' ],
            'hyperactivity'            => [ 'title' => 'Hyperactivity', 'type' => 'clinical' ],
            'aggression'               => [ 'title' => 'Aggression', 'type' => 'clinical' ],
            'conduct_problems'         => [ 'title' => 'Conduct Problems', 'type' => 'clinical' ],
            'internalizing_problems'   => [ 'title' => 'Internalizing Problems', 'type' => 'clinical' ],
            'anxiety'                  => [ 'title' => 'Anxiety', 'type' => 'clinical' ],
            'depression'               => [ 'title' => 'Depression', 'type' => 'clinical' ],
            'somatization'             => [ 'title' => 'Somatization', 'type' => 'clinical' ],
            'school_problems'          => [ 'title' => 'School Problems', 'type' => 'clinical' ],
            'attention_problems'       => [ 'title' => 'Attention Problems', 'type' => 'clinical' ],
            'learning_problems'        => [ 'title' => 'Learning Problems', 'type' => 'clinical' ],
            'behavioral_symptoms'      => [ 'title' => 'Behavioral Symptoms', 'type' => 'clinical' ],
            'atypicality'              => [ 'title' => 'Atypicality', 'type' => 'clinical' ],
            'withdrawal'               => [ 'title' => 'Withdrawal', 'type' => 'clinical' ],
            'Attention'                => [ 'title' => 'Attention Problems', 'type' => 'clinical' ],
            'adaptive'                 => [ 'title' => 'Adaptive Skills', 'type' => 'adaptive' ],
            'adaptability'             => [ 'title' => 'Adaptability', 'type' => 'adaptive' ],
            'social_skills'            => [ 'title' => 'Social Skills', 'type' => 'adaptive' ],
            'leadership'               => [ 'title' => 'Leadership', 'type' => 'adaptive' ],
            'daily_living'             => [ 'title' => 'Daily Living Skills', 'type' => 'adaptive' ],
            'functional_communication' => [ 'title' => 'Functional Communication', 'type' => 'adaptive' ],
            'study_skills'             => [ 'title' => 'Study Skills', 'type' => 'adaptive' ],
        ];

        $scoresEvaluated = [];
        $scaleRanges     = [];
        foreach ( $scaleMeta as $sKey => $m ) {
            $raterScores = [];
            for ( $r = 1; $r <= max( 3, $activeRatersCount ); $r++ ) {
                $rawVal            = $inputData[ "{$sKey}_{$r}" ]
                    ?? ( $inputData[ "{$sKey}_" . ( $r - 1 ) ]
                    ?? ( 1 === $r ? ( $inputData[ $sKey ] ?? ( $inputData['scores'][ $sKey ] ?? null ) ) : null ) );
                $raterScores[ $r ] = AssessmentScorer::classifyTScoreByProfile( $rawVal, $m['type'], $tScoreProfile );
            }
            $scoresEvaluated[ $sKey ] = [
                'title'      => $m['title'],
                'scale_type' => $m['type'],
                'profile'    => $tScoreProfile,
                'raters'     => $raterScores,
            ];
            $scaleRanges[ $sKey ]     = [
                1 => $raterScores[1]['range'] ?? 'Average',
                2 => $raterScores[2]['range'] ?? 'Average',
                3 => $raterScores[3]['range'] ?? 'Average',
            ];
        }

        $html = '';

        if ( ! $hideSocial ) {
            $html .= "<p class='Social-body_n'>Social/Emotional Functioning</p>";

            $additionalInformers = '';
            if ( $activeRatersCount >= 3 ) {
                $additionalInformers = " (<span class='informer_one'>{$inf_one}</span>), {$rel_two} (<span class='informer_tow'>{$inf_two}</span>), and {$rel_thr} (<span class='informant_three'>{$inf_thr}</span>).";
            } elseif ( 2 === $activeRatersCount ) {
                $additionalInformers = ", {$inf_one} and {$rel_two}, {$inf_two}.";
            } else {
                $additionalInformers = ", {$inf_one}.";
            }

            $html .= "<p class='Social-body_n'>Social-emotional development includes an individual's experience, expression, and management of emotions and behaviors. It includes the ability to establish positive and rewarding relationships with others and encompasses how well we manage ourselves as well as our interactions with others. In order to obtain information regarding {$possName} behavior at school and at home, the Behavior Assessment System for Children, Third Edition (BASC-3) was completed by {$his_her} {$rel_one}{$additionalInformers} The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality. {$possName} functioning was assessed through three broad Composites: Externalizing Problems, Internalizing Problems, and School Problems.</p>";

            // 1. Externalizing Problems
            $r1 = $scaleRanges['externalizing_problems'][1];
            $del = $this->getBasc3RaterAgreement( 'externalizing_problems', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'externalizing_problems', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'externalizing_problems', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='externalizing_p m_bt Social-body_n'><b>Externalizing Problems:</b> This composite is characterized by under-control of emotions/behaviors that result in disruptive behaviors, such as aggression, hyperactivity, and conduct problems. {$possName} overall level of Externalizing Problems was rated to be in the {$r1} range {$del}{$cons}{$elev}</p>";

            // 2. Hyperactivity
            $r1 = $scaleRanges['hyperactivity'][1];
            $del = $this->getBasc3RaterAgreement( 'hyperactivity', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'hyperactivity', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'hyperactivity', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='hyperactivity_p p_0 Social-body_n'><em>Hyperactivity:</em> This scale assesses a student's activity levels. Typical hyperactive behaviors may include fidgeting, constant movement, and wandering. {$name} received ratings within the {$r1} range {$del}{$cons}{$elev}</p>";

            // 3. Aggression
            $r1 = $scaleRanges['aggression'][1];
            $del = $this->getBasc3RaterAgreement( 'aggression', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'aggression', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'aggression', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='aggression_p p_0 Social-body_n'><em>Aggression:</em> This scale measures a student's tendency to act in a hostile manner. This may include both verbal (threatening others) and physical (hitting/kicking) behaviors. {$possName} levels of aggression were rated in the {$r1} range {$del}{$cons}{$elev}</p>";

            // 4. Conduct Problems (skip if preschool)
            if ( ! $isPreschool ) {
                $r1 = $scaleRanges['conduct_problems'][1];
                $del = $this->getBasc3RaterAgreement( 'conduct_problems', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
                $elev = $this->getBasc3ElevatedSentence( 'conduct_problems', $scoresEvaluated, $activeRatersCount, $name, $his_her );
                $html .= "<p class='conduct_p p_0 Social-body_n pre'><em>Conduct Problems:</em> This scale provides a measure of how often {$name} is getting in trouble and is characterized by anti-social behavior and marked impairments in relationships. {$name} received scores within the {$r1} range {$del}{$elev}</p>";
            }

            // 5. Internalizing Problems
            $r1 = $scaleRanges['internalizing_problems'][1];
            $del = $this->getBasc3RaterAgreement( 'internalizing_problems', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'internalizing_problems', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'internalizing_problems', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='internalizing_p m_bt Social-body_n'><b>Internalizing Problems:</b> This composite is characterized by over-control of emotions/behaviors and not usually marked by acting out behavior. Instead, internalizing behaviors are often described as social withdrawal and/or feelings of nervousness and sadness. {$possName} overall level of Internalizing Problems was rated within the {$r1} range {$del}{$cons}{$elev}</p>";

            // 6. Anxiety
            $r1 = $scaleRanges['anxiety'][1];
            $del = $this->getBasc3RaterAgreement( 'anxiety', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $elev = $this->getBasc3ElevatedSentence( 'anxiety', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='anxiety_p p_0 Social-body_n'><em>Anxiety:</em> This scale measures worry, nervousness, and/or unease. {$name} received scores within the {$r1} range {$del}{$elev}</p>";

            // 7. Depression
            $r1 = $scaleRanges['depression'][1];
            $del = $this->getBasc3RaterAgreement( 'depression', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $elev = $this->getBasc3ElevatedSentence( 'depression', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='depression_p p_0 Social-body_n'><em>Depression:</em> Depression is a common and serious medical illness that negatively affects how one feels, thinks, and acts. This scale measures observable behaviors associated with depression such as loss of interest and energy, verbalizations of sadness, and pervasive sad moods. {$name} received ratings within the {$r1} range {$del}{$elev}</p>";

            // 8. Somatization
            $r1 = $scaleRanges['somatization'][1];
            $del = $this->getBasc3RaterAgreement( 'somatization', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $elev = $this->getBasc3ElevatedSentence( 'somatization', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='somatization_p p_0 Social-body_n'><em>Somatization:</em> This scale assesses complaints of physical ailments or oversensitivity to seemingly normal physical problems. {$name} received scores within the {$r1} range {$del}{$elev}</p>";

            // 9. School Problems & Learning Problems (skip if preschool)
            if ( ! $isPreschool ) {
                $r1_sch = $scaleRanges['school_problems'][1];
                $r2_sch = $scaleRanges['school_problems'][2];
                $r3_sch = $scaleRanges['school_problems'][3];

                $r1_lea = $scaleRanges['learning_problems'][1];
                $r2_lea = $scaleRanges['learning_problems'][2];
                $r3_lea = $scaleRanges['learning_problems'][3];

                $sc_pro_ad = '';
                $sc_pro_ae = '';
                $sc_pro_af = '';
                $le_pro_ad = '';
                $le_pro_ae = '';
                $le_pro_af = '';

                if ( $isTeacher1 ) {
                    $sc_pro_ad = " {$possName} {$rel_one} reported concerns within the {$r1_sch} range for overall school problems.";
                    $le_pro_ad = " {$possName} {$rel_one} reported concerns within the {$r1_lea} range for overall learning problems.";
                } else {
                    $sc_pro_ad = " An overall composite for school problems is only obtained when the BASC-3 is completed by a teacher/educator.";
                    $le_pro_ad = " A score for learning problems is only obtained when the BASC-3 is completed by a teacher/educator.";
                }

                if ( $activeRatersCount >= 2 ) {
                    if ( $isTeacher1 && $isTeacher2 ) {
                        $sc_pro_ad = " {$possName} {$rel_one} reported concerns within the {$r1_sch} range for overall school problems and {$inf_two} reported {$r2_sch} functioning.";
                        $le_pro_ad = " {$possName} {$rel_one} reported concerns within the {$r1_lea} range for learning problems and {$inf_two} reported {$r2_lea} functioning.";
                    } elseif ( $isTeacher2 ) {
                        $sc_pro_ae = " {$possName} {$rel_two} reported concerns within the {$r2_sch} range for overall school problems.";
                        $le_pro_ae = " {$possName} {$rel_two} reported concerns within the {$r2_lea} range for learning problems.";
                    }

                    if ( $activeRatersCount >= 3 ) {
                        $sc_pro_af = " Additionally, {$inf_thr} reported concerns within the {$r3_sch} range on this composite.";
                        $le_pro_af = " Additionally, {$inf_thr} reported concerns within the {$r3_lea} range on this measure.";
                    }
                }

                $html .= "<p class='school_problems_p p_0 Social-body_n pre'><b>School Problems:</b> This composite reflects academic difficulties including problems of motivation, attention, learning, and cognition. A high score on this composite is a sign that {$name} displays behaviors that are very likely to interfere with achievement.{$sc_pro_ad}{$sc_pro_ae}{$sc_pro_af}</p>";

                // 10. Attention Problems
                $r1 = $scaleRanges['attention_problems'][1];
                $del = $this->getBasc3RaterAgreement( 'attention_problems', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
                $cons = $this->getBasc3ConsistencySentence( 'attention_problems', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
                $elev = $this->getBasc3ElevatedSentence( 'attention_problems', $scoresEvaluated, $activeRatersCount, $name, $his_her );
                $html .= "<p class='attention_p p_0 Social-body_n pre'><em>Attention Problems:</em> This scale measures distractibility and focus. {$possName} attention was rated within the {$r1} range {$del}{$cons}{$elev}</p>";

                // 11. Learning Problems
                $html .= "<p class='learning_problems_p p_0 Social-body_n pre'><em>Learning Problems:</em> This scale measures aspects related to academic achievement, such as getting poor grades and having trouble keeping up in class.{$le_pro_ad}{$le_pro_ae}{$le_pro_af}</p>";
            }

            // 12. Behavioral Symptoms
            $r1 = $scaleRanges['behavioral_symptoms'][1];
            $del = $this->getBasc3RaterAgreement( 'behavioral_symptoms', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'behavioral_symptoms', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'behavioral_symptoms', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='behavioral_p m_bt Social-body_n'><b>Behavioral Symptoms:</b> This composite is a total score that consists of Hyperactivity, Aggression, Depression, Attention, Atypicality, and Withdrawal. While the majority of these scales are discussed above, the Atypicality and Withdrawal scales are interpreted below. {$name} received scores within the {$r1} range {$del}{$cons}{$elev}</p>";

            // 13. Atypicality
            $r1 = $scaleRanges['atypicality'][1];
            $del = $this->getBasc3RaterAgreement( 'atypicality', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'atypicality', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'atypicality', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='atypicality_p p_0 Social-body_n'><em>Atypicality:</em> This scale measures the tendency to behave in ways that are considered immature, odd, or seemingly disconnected. {$name} received scores within the {$r1} range {$del}{$cons}{$elev}</p>";

            // 14. Withdrawal
            $r1 = $scaleRanges['withdrawal'][1];
            $del = $this->getBasc3RaterAgreement( 'withdrawal', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
            $cons = $this->getBasc3ConsistencySentence( 'withdrawal', $scoresEvaluated, $activeRatersCount, $his_her, $possName, $isPreschool );
            $elev = $this->getBasc3ElevatedSentence( 'withdrawal', $scoresEvaluated, $activeRatersCount, $name, $his_her );
            $html .= "<p class='withdrawal_p p_0 Social-body_n'><em>Withdrawal:</em> This scale measures the tendency to avoid social interactions or shy away in social situations. {$name} received scores within the {$r1} range {$del}{$cons}{$elev}</p>";

            // Attention Problems in Preschool mode (placed immediately after Withdrawal)
            if ( $isPreschool ) {
                $r1 = $scaleRanges['Attention'][1] ?? ( $scaleRanges['attention_problems'][1] ?? 'Average' );
                $del = $this->getBasc3RaterAgreement( 'Attention', $scaleRanges, $activeRatersCount, $inf_one, $inf_two, $inf_thr );
                $elev = $this->getBasc3ElevatedSentence( 'Attention', $scoresEvaluated, $activeRatersCount, $name, $his_her );
                $html .= "<p class='attention2_p Social-body_n'><em>Attention Problems:</em> This scale measures distractibility and focus. {$possName} attention was rated within the {$r1} range {$del}{$elev}</p>";
            }
        }

        // Adaptive Behavior Section (Flowing Unified Paragraph)
        if ( ! $hideAdaptive ) {
            $html .= "<p class='adaptive-body_n'><b>Adaptive Behavior:</b></p>";

            $adapt_intro = "Adaptive behavior includes the age-appropriate behaviors necessary for an individual to live independently and to function safely and appropriately in daily life. Adaptive behaviors include real life skills such as grooming, dressing, safety, safe food handling, school rules, ability to work, money management, cleaning, making friends, social skills, and personal responsibility.";

            // If Social/Emotional was assessed, this introductory data collection sentence is omitted on the live site
            $adapt_info_collect = "";
            if ( $hideSocial ) {
                if ( $activeRatersCount >= 2 ) {
                    $adapt_info_collect = " In order to obtain information regarding {$possName} adaptive behavior at school and at home, the Behavior Assessment System for Children, Third Edition (BASC-3) was completed by {$his_her} {$rel_one}, {$inf_one} and {$his_her} {$rel_two}, {$inf_two}. The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality.";
                } else {
                    $adapt_info_collect = " In order to obtain information regarding {$possName} adaptive behavior, the Behavior Assessment System for Children, Third Edition (BASC-3) was completed by {$his_her} {$rel_one}, {$inf_one}. The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality.";
                }
            }

            $r1_overall_adapt = $scaleRanges['adaptive'][1] ?? 'Average';
            $r2_overall_adapt = $scaleRanges['adaptive'][2] ?? 'Average';

            $adapt_de1 = "";
            if ( $activeRatersCount >= 2 ) {
                if ( $r1_overall_adapt === $r2_overall_adapt ) {
                    $adapt_de1 = " by both {$his_her} {$rel_two} and {$rel_one}. This would suggest that {$possName} overall Adaptive skills are consistent across settings";
                } else {
                    $adapt_de1 = " by {$his_her} {$rel_one} and within the {$r2_overall_adapt} range by {$his_her} {$rel_two}, suggesting that {$his_her} overall Adaptive skills vary based on {$his_her} environment";
                }
            }

            // Rater 1 subscales breakdown
            $r1_adaptability = $scaleRanges['adaptability'][1] ?? 'Average';
            $r1_social       = $scaleRanges['social_skills'][1] ?? 'Average';
            $r1_leadership   = $scaleRanges['leadership'][1] ?? 'Average';
            $r1_func         = $scaleRanges['functional_communication'][1] ?? 'Average';

            $allR1MatchNonAvg = ( $r1_overall_adapt === $r1_adaptability && $r1_social === $r1_leadership && $r1_overall_adapt === $r1_leadership && $r1_func === $r1_overall_adapt && $r1_overall_adapt !== 'Average' );

            if ( $allR1MatchNonAvg ) {
                $lead_part = $isPreschool ? "" : " Leadership, and";
                $adapt_de2 = " More specifically, {$possName} {$rel_one} rated the areas of Adaptability, Social Skills,{$lead_part} Functional Communication to be within the {$r1_overall_adapt} range.";
            } else {
                $lead_part = $isPreschool ? " and" : " the area of Leadership to be within the {$r1_leadership} range, and";
                $adapt_de2 = " More specifically, {$possName} {$rel_one} rated the area of Adaptability to be within the {$r1_adaptability} range, the area of Social Skills to be within the {$r1_social} range,{$lead_part} the area of Functional Communication to be within the {$r1_func} range.";
            }

            // Study skills breakdown
            $r1_study = $scaleRanges['study_skills'][1] ?? null;
            $r2_study = $scaleRanges['study_skills'][2] ?? 'Average';
            $adapt_study = "";
            if ( ! $isPreschool && $activeRatersCount >= 2 ) {
                if ( null !== $r1_study && 'N/A' !== $r1_study ) {
                    $adapt_study = " Additionally, {$inf_one} rated the area of Study Skills to be within the {$r1_study} range, and {$inf_two} rated {$r2_study} concerns for study skills.";
                } else {
                    $adapt_study = " Additionally, {$inf_two} rated {$r2_study} concerns for study skills.";
                }
            }

            // Rater 2 subscales breakdown
            $condition_g = "";
            if ( $activeRatersCount >= 2 ) {
                $r2_adaptability = $scaleRanges['adaptability'][2] ?? 'Average';
                $r2_social       = $scaleRanges['social_skills'][2] ?? 'Average';
                $r2_leadership   = $scaleRanges['leadership'][2] ?? 'Average';
                $r2_func         = $scaleRanges['functional_communication'][2] ?? 'Average';

                if ( $r2_adaptability === $r2_social && $r2_adaptability === $r2_leadership && $r2_adaptability === $r2_func && $r2_adaptability !== 'Average' ) {
                    $lead_part = $isPreschool ? "" : " Leadership, and";
                    $condition_g = " Furthermore, {$possName} {$rel_two} rated the areas of Adaptability, Social Skills,{$lead_part} Functional Communication to be within the {$r2_adaptability} range.";
                } else {
                    $lead_part = $isPreschool ? " and" : " the area of Leadership to be within the {$r2_leadership} range, and";
                    $condition_g = " Furthermore, {$possName} {$rel_two} rated the area of Adaptability to be within the {$r2_adaptability} range, the area of Social Skills to be within the {$r2_social} range,{$lead_part} the area of Functional Communication to be within the {$r2_func} range.";
                }
            }

            // Daily living (if single rater and non-average)
            $adapt_daily = "";
            if ( 1 === $activeRatersCount ) {
                $r1_daily = $scaleRanges['daily_living'][1] ?? 'Average';
                if ( 'Average' !== $r1_daily ) {
                    $adapt_daily = " Additionally, {$inf_one} rated {$possName} daily living skills to be within the {$r1_daily} range.";
                }
            }

            // Rater 3 adaptability breakdown (3+ raters)
            $condition_z = "";
            if ( $activeRatersCount >= 3 ) {
                $r3_overall = $scaleRanges['adaptive'][3] ?? 'Average';
                $r3_adapt   = $scaleRanges['adaptability'][3] ?? 'Average';
                $r3_social  = $scaleRanges['social_skills'][3] ?? 'Average';
                $r3_lead    = $scaleRanges['leadership'][3] ?? 'Average';
                $r3_func    = $scaleRanges['functional_communication'][3] ?? 'Average';
                $r3_study   = $scaleRanges['study_skills'][3] ?? 'Average';

                $r3_allAvg = ( 'Average' === $r3_overall && 'Average' === $r3_adapt && 'Average' === $r3_social && 'Average' === $r3_study && 'Average' === $r3_lead && 'Average' === $r3_func );

                if ( $r3_allAvg ) {
                    $condition_z = " {$inf_thr}, {$possName} {$rel_thr}, indicated Average functioning across all domains of adaptability.";
                } else {
                    $condition_z = " {$inf_thr}, {$possName} {$rel_thr}, indicated functioning within the {$r3_overall} range for overall adaptability. More specifically, {$inf_thr} reported {$possName} ability to adapt to changes within the {$r3_adapt} range, {$his_her} ability to interact with peers within the {$r3_social} range, {$his_her} ability to utilize effective Study Skills within the {$r3_study} range, and {$his_her} ability to lead a group effectively within the {$r3_lead} range. Additionally, {$inf_thr} reported {$possName} ability to communicate effectively at school within the {$r3_func} range.";
                }
            }

            $html .= "<p class='adaptive-body_n'>{$adapt_intro}{$adapt_info_collect} Adaptive scales measure positive behaviors, so higher scores indicate more adaptive behaviors and better adjustment. {$possName} overall Adaptive skills were rated to be in the {$r1_overall_adapt} range{$adapt_de1}.{$adapt_de2}{$adapt_study}{$condition_g}{$adapt_daily}{$condition_z}</p>";
        }

        // Validity / Response Pattern Section
        if ( ! $hideSocial ) {
            $f = [
                $inputData['f_index'] ?? 'acceptable',
                $inputData['Response_Pattern'] ?? 'acceptable',
                $inputData['Consistency'] ?? 'acceptable',
            ];
            $o = [
                $inputData['f_index1'] ?? 'acceptable',
                $inputData['Response_Pattern1'] ?? 'acceptable',
                $inputData['Consistency1'] ?? 'acceptable',
            ];

            $acc_count  = count( array_filter( $f, fn( $v ) => 'acceptable' === $v ) );
            $con_count  = 3 - $acc_count;
            $acc_count1 = count( array_filter( $o, fn( $v ) => 'acceptable' === $v ) );
            $con_count1 = 3 - $acc_count1;

            $bb = "";
            if ( 3 === $acc_count ) {
                $bb = " {$inf_one}'s rating consistency, response pattern, and F-Index were considered to be acceptable, indicating that these ratings are likely a valid representation of {$possName} current behavior/social-emotional functioning.";
            } elseif ( 3 === $con_count ) {
                $caution_val = ( 'extreme caution' === $f[0] && 'extreme caution' === $f[1] && 'extreme caution' === $f[2] ) ? 'extreme caution' : 'caution';
                $bb = " {$inf_one}'s rating consistency, response pattern, and F-Index indicated that the results of these ratings should be interpreted with {$caution_val}, and are likely not a valid representation of {$possName} current behavior/social-emotional functioning.";
            } else {
                $bb = " {$inf_one}'s ratings indicated caution, and should be interpreted accordingly.";
            }

            $validity_extra = "";
            if ( $activeRatersCount >= 2 ) {
                $be = ( $acc_count === $acc_count1 ) ? " Likewise," : " Additionally,";
                $bf = "";
                if ( 3 === $acc_count1 ) {
                    $bf = " {$inf_two}'s rating consistency, response pattern, and F-Index were considered to be acceptable.";
                } elseif ( 3 === $con_count1 ) {
                    $caution_val = ( 'extreme caution' === $o[0] && 'extreme caution' === $o[1] && 'extreme caution' === $o[2] ) ? 'extreme caution' : 'caution';
                    $bf = " {$inf_two}'s rating consistency, response pattern, and F-Index indicated that the results of these ratings should be interpreted with {$caution_val}, and are likely not a valid representation of {$possName} current behavior/social-emotional functioning.";
                } else {
                    $bf = " {$inf_two}'s ratings indicated caution, and should be interpreted accordingly.";
                }

                $bg = "";
                if ( 'acceptable' !== $o[1] || 'acceptable' !== $f[1] ) {
                    $bg = " The response pattern index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N).";
                }
                $bh = "";
                if ( 'acceptable' !== $o[2] || 'acceptable' !== $f[2] ) {
                    $bh = " The consistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way.";
                }

                $validity_extra = $be . $bf . $bg . $bh;
            }

            $bi = "";
            if ( $activeRatersCount >= 3 ) {
                $tr_f = $inputData['f_index2'] ?? 'acceptable';
                $tr_p = $inputData['Response_Pattern2'] ?? 'acceptable';
                $tr_c = $inputData['Consistency1'] ?? ( $inputData['Consistency2'] ?? 'acceptable' );

                if ( 'acceptable' === $tr_f && 'acceptable' === $tr_p && 'acceptable' === $tr_c ) {
                    $bi = " Additionally, {$inf_thr}'s answers were considered acceptable for F-index, response pattern, and consistency.";
                } else {
                    $bi = " Additionally, {$inf_thr}'s answers were in the {$tr_f} range for F-index, {$tr_p} range for response pattern, and within the {$tr_c} range for consistency.";
                }
            }

            $html .= "<p class='ch1 Social-body_n'>The BASC-3 Rating Scales assess a rater's response pattern to determine how results should be interpreted.{$bb}{$validity_extra}{$bi}</p>";
        }

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $html,
        ];
    }

    private function getBasc3RaterAgreement(
        string $sKey,
        array $scaleRanges,
        int $activeRatersCount,
        string $inf_one,
        string $inf_two,
        string $inf_thr
    ): string {
        $r1 = $scaleRanges[ $sKey ][1] ?? 'Average';
        $r2 = $scaleRanges[ $sKey ][2] ?? 'Average';
        $r3 = $scaleRanges[ $sKey ][3] ?? 'Average';

        if ( 1 === $activeRatersCount ) {
            if ( 'attention_problems' === $sKey || 'Attention' === $sKey ) {
                return "by {$inf_one}.";
            }
            if ( 'behavioral_symptoms' === $sKey ) {
                return "on this composite.";
            }
            return "on this measure.";
        }

        if ( 2 === $activeRatersCount ) {
            if ( $r1 === $r2 ) {
                return "by both {$inf_one} and {$inf_two}";
            }
            return "by {$inf_one} but within the {$r2} range by {$inf_two}";
        }

        // 3+ raters
        if ( $r1 === $r2 && $r2 === $r3 ) {
            if ( in_array( $sKey, [ 'somatization', 'depression', 'anxiety', 'conduct_problems', 'withdrawal', 'atypicality', 'behavioral_symptoms' ], true ) ) {
                return "by all evaluators on this measure";
            }
            if ( 'attention_problems' === $sKey || 'Attention' === $sKey ) {
                return "by all evaluators on measures of attention";
            }
            return "by all evaluators";
        }

        return "by {$inf_one}, within the {$r2} range by {$inf_two}, and within the {$r3} range by {$inf_thr}";
    }

    private function getBasc3ConsistencySentence(
        string $sKey,
        array $scoresEvaluated,
        int $activeRatersCount,
        string $his_her,
        string $possName,
        bool $isPreschool = false
    ): string {
        // In Preschool mode, consistency across settings sentences are not used.
        if ( $activeRatersCount < 2 || $isPreschool ) {
            return '';
        }

        // Suppress consistency sentence if ANY rater is elevated (>= 60) on this scale,
        // which matches the live site behavior (dec_val[p] < 60 && sec_val[p] < 60 && thr_val[p] < 60).
        for ( $r = 1; $r <= max( 2, $activeRatersCount ); $r++ ) {
            $score = $scoresEvaluated[ $sKey ]['raters'][ $r ]['score'] ?? null;
            if ( null !== $score && (float) $score >= 60 ) {
                return '';
            }
        }

        return match ( $sKey ) {
            'externalizing_problems' => ", suggesting that {$his_her} externalizing behaviors are consistent across settings.",
            'hyperactivity'          => ", suggesting that {$his_her} hyperactive behaviors are consistent across settings.",
            'aggression'             => ", suggesting that {$his_her} aggressive behaviors are consistent across settings.",
            'internalizing_problems' => ", indicating that {$possName} internalizing behaviors are consistent across settings.",
            'attention_problems',
            'Attention'              => ", indicating that these behaviors are seen across settings.",
            'behavioral_symptoms'    => ", suggesting that these behaviors are observed across settings.",
            'atypicality'            => ", suggesting that these behaviors are observed across settings.",
            'withdrawal'             => ", suggesting that these behaviors are observed across settings.",
            default                  => '.',
        };
    }

    private function getBasc3ElevatedSentence(
        string $sKey,
        array $scoresEvaluated,
        int $activeRatersCount,
        string $name,
        string $his_her
    ): string {
        $elevated = false;
        for ( $r = 1; $r <= max( 2, $activeRatersCount ); $r++ ) {
            $score = $scoresEvaluated[ $sKey ]['raters'][ $r ]['score'] ?? null;
            if ( null !== $score && (float) $score >= 60 ) {
                $elevated = true;
                break;
            }
        }
        if ( ! $elevated ) {
            return '';
        }

        return match ( $sKey ) {
            'externalizing_problems' => " The presence of an elevated score indicates that {$name} exhibits externalizing problems and may struggle to keep {$his_her} impulses under control and has been observed to act out in various ways that are detrimental to {$his_her} academic success.",
            'hyperactivity'          => " The presence of an elevated score indicates that {$name} exhibits hyperactive behaviors such as rushing through work and acting without thinking.",
            'aggression'             => " The presence of an elevated score suggests that {$name} may exhibit aggressive behaviors such as using foul language or hitting/kicking others.",
            'conduct_problems'       => " The presence of an elevated score indicates that {$name} may get in trouble a lot and has significant difficulties maintaining interpersonal relationships.",
            'internalizing_problems' => " The presence of an elevated score indicates that {$name} may be struggling with depression, anxiety, and/or low self-esteem.",
            'anxiety'                => " The presence of an elevated score suggests that {$name} may be suffering with anxiety.",
            'depression'             => " The presence of an elevated score suggests that {$name} may be suffering with depression and thoughts of helplessness, hopelessness, and, in extreme cases, suicide.",
            'somatization'           => " The presence of an elevated score suggests that {$name} may ask to go to the nurse's office a lot and may have an underlying problem associated with {$his_her} physical ailments that is not physical in nature.",
            'attention_problems',
            'Attention'              => " The presence of an elevated score suggests that {$name} may have trouble maintaining focus, be distracted by seemingly normal stimuli, and stray from the task at hand often.",
            'behavioral_symptoms'    => " The presence of an elevated score suggests that {$name} may often shy away from {$his_her} peers or display odd/unusual behaviors.",
            'atypicality'            => " The presence of an elevated score suggests that {$name} may behave in ways that others would consider odd or inappropriate.",
            'withdrawal'             => " The presence of an elevated score suggests that {$name} may be struggling with anxiety about social interactions.",
            default                  => '',
        };
    }

    /**
     * Dedicated Processor for BASC-3 Self-Report of Personality (SRP Child / Adolescent)
     */
    private function processBasc3SelfReport(
        array $templateRow,
        array $inputData,
        array $context,
        array $scales
    ): array {
        $first_name = $context['name'];
        $possName   = $context['possessive_name'];
        $he_she     = $context['subject'];
        $his_her    = $context['possessive'];
        $him_her    = $context['object'];
        $isChild    = ! empty( $inputData['SRP_button'] ) && ( '1' === (string) $inputData['SRP_button'] || true === $inputData['SRP_button'] );

        $classifyClinical = static function( $val ): array {
            $v = is_numeric( $val ) ? (float) $val : 50;
            if ( $v < 60 ) {
                return [ 'desc' => 'average', 'stars' => '', 'val' => $v ];
            }
            if ( $v < 70 ) {
                return [ 'desc' => 'at-risk', 'stars' => '*', 'val' => $v ];
            }
            return [ 'desc' => 'clinically significant', 'stars' => '**', 'val' => $v ];
        };

        $classifyAdaptive = static function( $val ): array {
            $v = is_numeric( $val ) ? (float) $val : 50;
            if ( $v < 31 ) {
                return [ 'desc' => 'clinically significant', 'stars' => '**', 'val' => $v ];
            }
            if ( $v < 41 ) {
                return [ 'desc' => 'at-risk', 'stars' => '*', 'val' => $v ];
            }
            return [ 'desc' => 'average', 'stars' => '', 'val' => $v ];
        };

        $scoresEvaluated = [];
        $rawScores       = [];

        $getScore = static function( string $key, bool $isAdaptive = false ) use ( &$scoresEvaluated, &$rawScores, $inputData, $classifyClinical, $classifyAdaptive ): array {
            $val = $inputData[ "{$key}_1" ] ?? ( $inputData[ $key ] ?? 50 );
            $cls = $isAdaptive ? $classifyAdaptive( $val ) : $classifyClinical( $val );
            $rawScores[ $key ]       = $cls;
            $scoresEvaluated[ $key ] = [
                'score'  => $cls['val'],
                'range'  => $cls['desc'],
                'stars'  => $cls['stars'],
                'raters' => [
                    1 => [
                        'score' => $cls['val'],
                        'range' => $cls['desc'],
                        'stars' => $cls['stars'],
                    ],
                ],
            ];
            return $cls;
        };

        $school_pro = $getScore( 'school_problems' );
        $att_school = $getScore( 'attitude_to_school' );
        $att_teach  = $getScore( 'attitude_to_teachers' );
        $sens_seek  = $getScore( 'sensation_seeking' );

        $int_prob   = $getScore( 'internalizing_problems' );
        $atypical   = $getScore( 'atypicality' );
        $locus_ctrl = $getScore( 'locus_of_control' );
        $somatiz    = $getScore( 'somatization' );
        $soc_stress = $getScore( 'social_stress' );
        $anxiety    = $getScore( 'anxiety' );
        $depress    = $getScore( 'depression' );
        $sense_inad = $getScore( 'sense_of_inadequacy' );

        $inatt_hyp  = $getScore( 'inattention_hyperactivity' );
        $att_prob   = $getScore( 'attention_problems' );
        $hyperact   = $getScore( 'hyperactivity' );

        $pers_adj   = $getScore( 'personal_adjustment', true );
        $rel_parent = $getScore( 'relations_with_parents', true );
        $inter_rel  = $getScore( 'interpersonal_relations', true );
        $self_est   = $getScore( 'selfesteem', true );
        $self_rel   = $getScore( 'selfreliance', true );

        $esi        = $getScore( 'selfre' );

        $f_index  = strtolower( trim( (string) ( $inputData['f_index'] ?? 'acceptable' ) ) );
        $l_index  = strtolower( trim( (string) ( $inputData['L_Index'] ?? 'acceptable' ) ) );
        $v_index  = strtolower( trim( (string) ( $inputData['V_Index'] ?? 'acceptable' ) ) );

        $p1 = "<p class=''>In order to obtain information regarding {$possName} interpretation of {$his_her} social/emotional and behavioral functioning, the Behavior Assessment System for Children, Third Edition Self Report of Personality (BASC-3 SRP) was completed by {$first_name}. The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality, including positive (adaptive) as well as negative (clinical) dimensions. Scores in the clinically significant range suggest a high level of maladjustment, while scores in the at-risk range identify either a significant problem that may not be severe enough to require formal treatment or the potential of developing a problem that needs careful monitoring.</p>";

        $att_school_text = ( $att_school['val'] >= 60 ) ? 'dislikes school and often wishes to be elsewhere' : 'likes school';
        $att_teach_text  = ( $att_teach['val'] >= 60 ) ? 'difficulty with, and/or disliking,' : 'age appropriate attitudes towards';
        $sens_seek_text  = ( $sens_seek['val'] >= 60 ) ? "more often than {$his_her} same aged peers." : "as often as others {$his_her} age.";

        $p2 = "<p class=''>School Problems: This composite measures attitudes and behaviors that may be problematic for school success. {$first_name} reported {$school_pro['desc']} functioning on this composite. {$possName} ratings indicate that {$he_she} {$att_school_text}. Additionally, {$first_name} described {$him_her}self as having {$att_teach_text} {$his_her} teachers.";
        if ( ! $isChild ) {
            $p2 .= " Additionally, {$first_name} reported engaging in risky behaviors {$sens_seek_text}";
        }
        $p2 .= '</p>';

        $atyp_text = ( $atypical['val'] >= 60 ) ? "thoughts that would be considered atypical for {$his_her} age," : 'appropriate thoughts and perceptions,';
        $loc_text  = ( $locus_ctrl['val'] >= 60 ) ? 'little control over events occurring' : 'control over events that occur';
        $soc_text  = ( $soc_stress['val'] >= 60 ) ? 'often feels lonely, isolated, and/or bullied.' : 'does not often experience feelings of loneliness.';
        $som_text  = ( $somatiz['val'] >= 60 ) ? 'has a tendency of experiencing an odd amount of health-related problems such as headaches, sore muscles, stomach ailments, and/or dizziness.' : 'does not have a tendency of experiencing an odd amount of health-related problems.';

        $anx_elev = $anxiety['val'] >= 60;
        $dep_elev = $depress['val'] >= 60;
        if ( $anx_elev && $dep_elev ) {
            $anx_dep_text = "{$first_name} reported a high level of anxiety-based feelings, as well as feelings of depression.";
        } elseif ( $anx_elev && ! $dep_elev ) {
            $anx_dep_text = "while {$first_name} did not report significant depressed feelings {$he_she} did report high levels of anxiety.";
        } elseif ( ! $anx_elev && $dep_elev ) {
            $anx_dep_text = "while {$first_name} did not report significant anxiety-based feelings {$he_she} did report high levels of depression.";
        } else {
            $anx_dep_text = "{$first_name} did not report significant anxiety-based feelings or feelings of depression.";
        }

        $inad_text = ( $sense_inad['val'] >= 60 ) ? "sometimes being dissatisfied with {$his_her} ability to perform a variety of tasks even when putting forth a substantial effort." : 'being satisfied with ' . $his_her . ' ability to perform a variety of tasks.';

        $p3 = "<p class=''>Internalizing Problems: This composite measures depression, anxiety, and similar difficulties that are not usually marked by acting out behaviors. {$first_name} reported {$int_prob['desc']} functioning on this composite. More specifically, {$first_name} reported that {$he_she} has {$atyp_text} feels that {$he_she} has {$loc_text} in {$his_her} life, and reported that {$he_she} {$soc_text}";
        if ( ! $isChild ) {
            $p3 .= " {$first_name} indicated that {$he_she} {$som_text}";
        }
        $p3 .= " Additionally, {$anx_dep_text} Furthermore, {$first_name} reported {$inad_text}</p>";

        $att_elev = $att_prob['val'] >= 60;
        $hyp_elev = $hyperact['val'] >= 60;
        if ( $att_elev && $hyp_elev ) {
            $att_hyp_text = "reported a high level of difficulty sustaining attention, as well as high levels of hyperactivity that could be affecting {$his_her} classroom performance.";
        } elseif ( $att_elev && ! $hyp_elev ) {
            $att_hyp_text = "did not report significant hyperactivity problems, but did report attention difficulties that could be affecting {$his_her} classroom performance.";
        } elseif ( ! $att_elev && $hyp_elev ) {
            $att_hyp_text = "did not report significant difficulties sustaining attention, but did report high levels of hyperactive behavior that could be affecting {$his_her} classroom performance.";
        } else {
            $att_hyp_text = 'did not report significant concerns for attention or hyperactivity.';
        }

        $p4 = "<p class=''>Inattention/Hyperactivity: This composite measures problems with attention, hyperactivity, and impulsivity. {$possName} Inattention/Hyperactivity composite was reported to be within the {$inatt_hyp['desc']} range of functioning. Specifically, {$first_name} {$att_hyp_text}</p>";

        $rel_par_text   = ( $rel_parent['val'] < 41 ) ? 'strained' : 'positive';
        $inter_rel_text = ( $inter_rel['val'] < 41 ) ? 'has difficulty with peer relations,' : "feels liked and respected by {$his_her} peers,";
        $self_est_text  = ( $self_est['val'] < 41 ) ? "reports negative feelings about {$his_her} self-worth" : "generally reports liking {$his_her} self";
        $self_rel_text  = ( $self_rel['val'] < 41 ) ? "has insecurities about {$his_her} abilities." : "feels confident in {$his_her} own abilities.";

        $p5 = "<p class=''>Personal Adjustment: This composite measures problems with interpersonal relations and perceptions of self. {$first_name} rated {$his_her} overall personal adjustment to be within the {$pers_adj['desc']} range. More specifically, {$first_name} reported that {$he_she} has a {$rel_par_text} relationship with {$his_her} parents, {$inter_rel_text} {$self_est_text}, and often {$self_rel_text}</p>";

        $p6 = "<p class='ch1'>The BASC-3 Rating Scales assess a rater's response pattern to determine how results should be interpreted. {$possName} rating consistency, response pattern, and F-Index were considered to be {$f_index}, indicating that these ratings are likely a valid representation of {$possName} current behavior/social-emotional functioning. Additionally, {$his_her} ratings were in the {$l_index} range for the L-Index, and within the {$v_index} range for the V-Index.</p>";

        $html = $p1 . $p2 . $p3 . $p4 . $p5 . $p6;

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $html,
        ];
    }

    /**
     * Specialized processor for BRIEF-2 Behavior Rating Inventory of Executive Function, Second Edition.
     * Replicates the exact 5-paragraph clinical synthesis from the live ClickReport application.
     */
    private function processBrief2(
        array $templateRow,
        array $inputData,
        array $context,
        array $informants,
        int $activeRatersCount,
        string $tScoreProfile
    ): array {
        $examinee_name   = ! empty( $context['name'] ) ? esc_html( $context['name'] ) : 'the examinee';
        $examinee_name_2 = ! empty( $context['name'] ) ? esc_html( $context['name'] ) : 'The Examinee';
        $his_her         = ! empty( $context['possessive'] ) ? esc_html( $context['possessive'] ) : 'his';

        $infor_dropbox1  = esc_html( $informants[0]['relation'] ?? 'mother' );
        $informer_title0 = esc_html( $informants[0]['name'] ?? 'Rater One' );

        $infor_dropbox2  = esc_html( $informants[1]['relation'] ?? 'general education teacher' );
        $informer_title1 = esc_html( $informants[1]['name'] ?? 'Rater Two' );

        $infor_dropbox3  = esc_html( $informants[2]['relation'] ?? 'general education teacher' );
        $informer_title2 = esc_html( $informants[2]['name'] ?? 'Rater Three' );

        // Extract Validity Scales
        $negOne = strtolower( trim( (string) ( $inputData['negativity'] ?? ( $inputData['negOne'] ?? 'acceptable' ) ) ) );
        $incOne = strtolower( trim( (string) ( $inputData['inconsistency'] ?? ( $inputData['incOne'] ?? 'acceptable' ) ) ) );
        $infOne = strtolower( trim( (string) ( $inputData['infrequency'] ?? ( $inputData['infOne'] ?? 'acceptable' ) ) ) );

        $negTwo = strtolower( trim( (string) ( $inputData['negativity1'] ?? ( $inputData['negTwo'] ?? 'acceptable' ) ) ) );
        $incTwo = strtolower( trim( (string) ( $inputData['inconsistency1'] ?? ( $inputData['incTwo'] ?? 'acceptable' ) ) ) );
        $infTwo = strtolower( trim( (string) ( $inputData['infrequency1'] ?? ( $inputData['infTwo'] ?? 'acceptable' ) ) ) );

        $negThree = strtolower( trim( (string) ( $inputData['negativity2'] ?? ( $inputData['negThree'] ?? 'acceptable' ) ) ) );
        $incThree = strtolower( trim( (string) ( $inputData['inconsistency2'] ?? ( $inputData['incThree'] ?? 'acceptable' ) ) ) );
        $infThree = strtolower( trim( (string) ( $inputData['infrequency2'] ?? ( $inputData['infThree'] ?? 'acceptable' ) ) ) );

        $scaleMeta = [
            'gec'                       => [ 'title' => 'Global Executive Composite (GEC)', 'type' => 'clinical' ],
            'bri'                       => [ 'title' => 'Behavior Regulation Index (BRI)', 'type' => 'clinical' ],
            'inhibit'                   => [ 'title' => 'Inhibit', 'type' => 'clinical' ],
            'selfmonitor'               => [ 'title' => 'Self-Monitor', 'type' => 'clinical' ],
            'eri'                       => [ 'title' => 'Emotion Regulation Index (ERI)', 'type' => 'clinical' ],
            'shift'                     => [ 'title' => 'Shift', 'type' => 'clinical' ],
            'emotional_control'         => [ 'title' => 'Emotional Control', 'type' => 'clinical' ],
            'cri'                       => [ 'title' => 'Cognitive Regulation Index (CRI)', 'type' => 'clinical' ],
            'initiate'                  => [ 'title' => 'Initiate', 'type' => 'clinical' ],
            'working_memory'            => [ 'title' => 'Working Memory', 'type' => 'clinical' ],
            'planorganize'              => [ 'title' => 'Plan/Organize', 'type' => 'clinical' ],
            'taskmonitor'               => [ 'title' => 'Task-Monitor', 'type' => 'clinical' ],
            'organization_of_materials' => [ 'title' => 'Organization of Materials', 'type' => 'clinical' ],
        ];

        $getRange = function( $val ) {
            if ( null === $val || '' === $val || 'N/A' === $val ) {
                return 'average';
            }
            $v = (float) $val;
            if ( $v > 59 ) {
                if ( $v >= 60 && $v <= 64 ) {
                    return 'mildly elevated';
                }
                if ( $v >= 65 && $v <= 69 ) {
                    return 'potentially clinically elevated';
                }
                return 'clinically elevated';
            }
            return 'average';
        };

        $scoresEvaluated = [];
        $rawScores       = [];
        $ranges          = [];

        foreach ( $scaleMeta as $sKey => $m ) {
            $raterScores = [];
            for ( $r = 1; $r <= max( 3, $activeRatersCount ); $r++ ) {
                $rawVal                   = $this->extractRaterScoreValue( $inputData, $sKey, $r );
                $raterScores[ $r ]        = AssessmentScorer::classifyTScoreByProfile( $rawVal, $m['type'], $tScoreProfile );
                $rawScores[ $sKey ][ $r ] = $rawVal;
                $ranges[ $sKey ][ $r ]    = $getRange( $rawVal );
            }
            $scoresEvaluated[ $sKey ] = [
                'title'      => $m['title'],
                'scale_type' => $m['type'],
                'profile'    => $tScoreProfile,
                'raters'     => $raterScores,
            ];
        }

        $executiveFunctioning        = '';
        $behavioral_regulation_index = '';
        $emotional_regulation_index  = '';
        $cognitive_regulation_index  = '';
        $brief_2_rating_salses       = '';

        if ( $activeRatersCount <= 1 ) {
            $executiveFunctioning .= "Executive functions are a collection of processes that are responsible for guiding, directing, and managing cognitive, emotional, and behavioral functions, particularly during active, novel problem solving. In order to obtain information regarding {$examinee_name}'s executive functioning, the Behavior Rating Inventory of Executive Function, Second Edition (BRIEF-2) was completed by {$his_her} {$infor_dropbox1}, {$informer_title0}. The BRIEF-2 is designed for individuals ages 5 to 18 for whom there may be a concern about self-regulation. BRIEF-2 T-scores have a mean of 50 and a standard deviation of 10. BRIEF-2 scores 60-64 are considered mildly elevated, scores 65-69 are considered potentially clinically elevated and scores of 70 and above are typically considered clinically elevated or clinically significant. {$examinee_name_2}'s functioning was assessed through three broad Composites: Behavior Regulation, Emotion Regulation, and Cognitive Regulation. The BRIEF-2 also provides a Global Executive Composite (GEC) score that serves as an overall estimate of executive functioning. {$examinee_name_2}'s GEC was rated to be within the " . $ranges['gec'][1] . ' range. ';

            $behavioral_regulation_index .= "Behavior Regulation Index: This composite represents a student's ability to regulate and monitor behavior effectively. {$examinee_name_2}'s overall level of behavior regulation problems was rated to be in the " . $ranges['bri'][1] . ' range.';
            $behavioral_regulation_index .= " Specifically, {$informer_title0} rated {$examinee_name}'s ability to control {$his_her} impulses (inhibit) within the " . $ranges['inhibit'][1] . " range, and {$his_her} ability to self-monitor within the " . $ranges['selfmonitor'][1] . ' range. ';

            $emotional_regulation_index .= "Emotional Regulation Index: The Emotion Regulation Index (ERI) represents a student's ability to regulate emotional responses including responding to changing situations. {$examinee_name_2}'s overall level of emotional regulation skills were considered to be within the " . $ranges['eri'][1] . ' range. ';
            $emotional_regulation_index .= "Specifically, {$informer_title0} rated {$examinee_name}'s ability to transition (Shift) to be within the " . $ranges['shift'][1] . " range, and ability to regulate {$his_her} emotions (Emotional Control) to be within the " . $ranges['emotional_control'][1] . ' range. ';

            $cognitive_regulation_index .= "Cognitive Regulation Index: The Cognitive Regulation Index (CRI) represents a student's ability to control and manage cognitive processes and problem-solve effectively. {$examinee_name_2}'s overall CRI ability was rated to be within the " . $ranges['cri'][1] . ' range. ';
            $cognitive_regulation_index .= "Specifically, {$informer_title0} rated {$examinee_name}'s ability to begin an activity or generate ideas (Initiate) to be within the " . $ranges['initiate'][1] . " range and {$his_her} ability to hold information in {$his_her} mind to be within the " . $ranges['working_memory'][1] . ' range. ';
            $cognitive_regulation_index .= "It was also reported that {$examinee_name}'s ability to set goals was considered to be within the " . $ranges['planorganize'][1] . " range, ability to check {$his_her} work to be within the " . $ranges['taskmonitor'][1] . " range, and ability to keep {$his_her} work/play area clean and orderly to be within the " . $ranges['organization_of_materials'][1] . ' range.';

            $brief_2_rating_salses .= "The BRIEF-2 Rating Scales assess a rater's response pattern to determine how results should be interpreted. ";
            if ( $negOne === 'acceptable' && $incOne === 'acceptable' && $infOne === 'acceptable' ) {
                $brief_2_rating_salses .= "{$informer_title0}'s Inconsistency, Infrequency, and Negativity Indices were all considered to be acceptable, indicating that these ratings are likely a valid representation of {$examinee_name}'s current behavioral functioning at this time. ";
            } elseif ( $negOne !== 'acceptable' && $incOne === 'acceptable' && $infOne === 'acceptable' ) {
                $brief_2_rating_salses .= "While {$informer_title0}'s ratings were considered acceptable for appropriate frequency and consistency, the Negativity scale indicates responses that were excessively negative, and the validity of these scores was in the {$negOne} range.";
            } elseif ( $negOne === 'acceptable' && $incOne === 'acceptable' && $infOne !== 'acceptable' ) {
                $brief_2_rating_salses .= "While {$informer_title0}'s ratings were considered acceptable for the Negativity Index and appropriate consistency, the Infrequency Index indicates that these scores are considered to have {$infOne} validity. ";
            } elseif ( $negOne === 'acceptable' && $incOne !== 'acceptable' && $infOne === 'acceptable' ) {
                $brief_2_rating_salses .= "While {$informer_title0}'s ratings were considered acceptable for the Negativity Index and Infrequency Index, the Inconsistency index indicates that these scores have {$incOne} validity. ";
            } else {
                $brief_2_rating_salses .= "{$informer_title0}'s Inconsistency, Infrequency, and Negativity Indices indicated that the results of these ratings should be interpreted with caution, and are likely not a valid representation of {$examinee_name}'s current behavioral functioning. ";
            }

            if ( $infOne !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Infrequency Index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N). ';
            }
            if ( $incOne !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Inconsistency Index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way. ';
            }
        } elseif ( 2 === $activeRatersCount ) {
            $executiveFunctioning .= "Executive functions are a collection of processes that are responsible for guiding, directing, and managing cognitive, emotional, and behavioral functions, particularly during active, novel problem solving. In order to obtain information regarding {$examinee_name}'s executive functioning at school and at home, the Behavior Rating Inventory of Executive Function, Second Edition (BRIEF-2) was completed by {$his_her} {$infor_dropbox1}, {$informer_title0} and {$infor_dropbox2}, {$informer_title1}. The BRIEF-2 is designed for individuals ages 5 to 18 for whom there may be a concern about self-regulation. BRIEF-2 T-scores have a mean of 50 and a standard deviation of 10. BRIEF-2 scores 60-64 are considered mildly elevated, scores 65-69 are considered potentially clinically elevated and scores of 70 and above are typically considered clinically elevated or clinically significant. {$examinee_name_2}'s GEC was within the ";

            if ( $rawScores['gec'][1] == $rawScores['gec'][2] ) {
                $executiveFunctioning .= $ranges['gec'][1] . " range by both {$informer_title0} and {$informer_title1}. ";
            } else {
                $executiveFunctioning .= $ranges['gec'][1] . " range by {$informer_title0} and within the " . $ranges['gec'][2] . " range by {$his_her} {$infor_dropbox2}.";
            }

            $behavioral_regulation_index .= "Behavior Regulation Index: This composite represents a student's ability to regulate and monitor behavior effectively. {$examinee_name}'s overall level of behavior regulation problems was rated to be in the ";

            if ( $rawScores['bri'][1] == $rawScores['bri'][2] ) {
                $behavioral_regulation_index .= $ranges['bri'][1] . " range by both {$informer_title0} and {$informer_title1}. ";
            } else {
                $behavioral_regulation_index .= $ranges['bri'][1] . " range by {$informer_title0} and within the " . $ranges['bri'][2] . " range by {$informer_title1}.";
            }

            if ( $rawScores['inhibit'][1] == $rawScores['inhibit'][2] && $rawScores['selfmonitor'][1] == $rawScores['selfmonitor'][2] ) {
                $behavioral_regulation_index .= " Specifically, {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to control {$his_her} impulses (inhibit) within the " . $ranges['inhibit'][1] . " range, and {$his_her} ability to self-monitor to be in the " . $ranges['selfmonitor'][1] . ' range. ';
            } else {
                $behavioral_regulation_index .= " Specifically, {$informer_title0} rated {$examinee_name}'s ability to control {$his_her} impulses (inhibit) within the " . $ranges['inhibit'][1] . " range, and {$his_her} ability to self-monitor in the " . $ranges['selfmonitor'][1] . " range. However, {$informer_title1} rated the Inhibit scale to be within the " . $ranges['inhibit'][2] . " range and the Self-Monitor scale to be within the " . $ranges['selfmonitor'][2] . ' range. ';
            }

            $emotional_regulation_index .= "Emotional Regulation Index: The Emotion Regulation Index (ERI) represents a student's ability to regulate emotional responses including responding to changing situations. {$examinee_name}'s overall level of emotional regulation skills were considered ";

            if ( $rawScores['eri'][1] == $rawScores['eri'][2] ) {
                $emotional_regulation_index .= ' to be within the ' . $ranges['eri'][1] . " range by both {$informer_title0} and {$informer_title1}. ";
            } else {
                $emotional_regulation_index .= ' to be within the ' . $ranges['eri'][1] . " range by {$informer_title0} and the " . $ranges['eri'][2] . " range by {$informer_title1}. ";
            }

            if ( $rawScores['shift'][1] == $rawScores['shift'][2] && $rawScores['emotional_control'][1] == $rawScores['emotional_control'][2] ) {
                $emotional_regulation_index .= " Specifically, both {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to transition (Shift) to be within the " . $ranges['shift'][1] . " range, and ability to regulate {$his_her} emotions (Emotional Control) to be within the " . $ranges['emotional_control'][1] . ' range. ';
            } else {
                $emotional_regulation_index .= " Specifically, {$informer_title0} rated {$examinee_name}'s ability to transition (Shift) to be within the " . $ranges['shift'][1] . " range, and ability to regulate {$his_her} emotions to be within the " . $ranges['emotional_control'][1] . " range. However, {$informer_title1} indicated " . $ranges['shift'][2] . ' functioning on the Shift scale and ' . $ranges['emotional_control'][2] . ' functioning on the Emotional Control scale. ';
            }

            $cognitive_regulation_index .= "Cognitive Regulation Index: The Cognitive Regulation Index (CRI) represents a student's ability to control and manage cognitive processes and problem-solve effectively. {$examinee_name}'s overall CRI ability was rated to be within the ";

            if ( $rawScores['cri'][1] == $rawScores['cri'][2] ) {
                $cognitive_regulation_index .= $ranges['cri'][1] . " range by {$informer_title0} and {$informer_title1}. ";
            } else {
                $cognitive_regulation_index .= $ranges['cri'][1] . " range by {$informer_title0} and within the " . $ranges['cri'][2] . " range by {$informer_title1}. ";
            }

            if ( $rawScores['initiate'][1] == $rawScores['initiate'][2] && $rawScores['working_memory'][1] == $rawScores['working_memory'][2] ) {
                $cognitive_regulation_index .= "Specifically, both {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to begin an activity or generate ideas (Initiate) to be within the " . $ranges['initiate'][1] . " range and {$his_her} ability to hold information in {$his_her} mind to be within the " . $ranges['working_memory'][1] . ' range. ';
            } else {
                $cognitive_regulation_index .= "{$informer_title0} rated {$examinee_name}'s ability to begin an activity or generate ideas (Initiate) to be within the " . $ranges['initiate'][1] . " range and {$his_her} ability to hold information in {$his_her} mind (Working Memory) to be within the " . $ranges['working_memory'][1] . " range. Whereas {$informer_title1} rated the Initiate scale within the " . $ranges['initiate'][2] . " range and the Working Memory scale within the " . $ranges['working_memory'][2] . ' range. ';
            }

            if ( $rawScores['planorganize'][1] == $rawScores['planorganize'][2] && $rawScores['taskmonitor'][1] == $rawScores['taskmonitor'][2] && $rawScores['organization_of_materials'][1] == $rawScores['organization_of_materials'][2] ) {
                $cognitive_regulation_index .= " They also both rated {$examinee_name}'s ability to set goals to be within the " . $ranges['planorganize'][1] . " range, ability to check {$his_her} work to be within the " . $ranges['taskmonitor'][1] . " range, and ability to keep {$his_her} work/play area clean and orderly to be within the " . $ranges['organization_of_materials'][1] . ' range.';
            } else {
                $cognitive_regulation_index .= " Additionally, {$informer_title0} rated {$examinee_name}'s ability to set goals (Plan/Organize) to be within the " . $ranges['planorganize'][1] . " range, ability to check {$his_her} work (Task-Monitor) to be within the " . $ranges['taskmonitor'][1] . " range, and ability to keep {$his_her} work/play area clean and orderly (Organization of Materials) to be within the " . $ranges['organization_of_materials'][1] . " range. {$informer_title1} indicated " . $ranges['planorganize'][2] . ' functioning for the Plan/Organize scale, ' . $ranges['taskmonitor'][2] . ' functioning for the Task-Monitoring scale, and ' . $ranges['organization_of_materials'][2] . ' functioning for the Organization of Materials scale. ';
            }

            $brief_2_rating_salses .= "The BRIEF-2 Rating Scales assess a rater's response pattern to determine how results should be interpreted. ";
            if ( $negOne === 'acceptable' && $incOne === 'acceptable' && $infOne === 'acceptable' ) {
                $brief_2_rating_salses .= "{$informer_title0}'s Inconsistency, Infrequency, and Negativity Indices were all considered to be acceptable, indicating that these ratings are likely a valid representation of {$examinee_name}'s current behavioral functioning at this time. ";
            } else {
                $brief_2_rating_salses .= "{$informer_title0}'s Inconsistency, Infrequency, and Negativity Indices indicated that the results of these ratings should be interpreted with caution. ";
            }

            if ( $negTwo === $negOne && $infTwo === $infOne && $incTwo === $incOne ) {
                $brief_2_rating_salses .= ' Likewise, ';
            } else {
                $brief_2_rating_salses .= ' Additionally, ';
            }

            if ( $negTwo === 'acceptable' && $infTwo === 'acceptable' && $incTwo === 'acceptable' ) {
                $brief_2_rating_salses .= "{$informer_title1}'s Inconsistency, Infrequency, and Negativity indices were considered to be acceptable.";
            } else {
                $brief_2_rating_salses .= "{$informer_title1}'s Inconsistency, Infrequency, and Negativity indices indicated that these ratings should be interpreted with caution.";
            }

            if ( $infOne !== 'acceptable' || $infTwo !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Infrequency index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N). ';
            }
            if ( $incOne !== 'acceptable' || $incTwo !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Inconsistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way. ';
            }
        } else {
            // 3 or more raters
            $executiveFunctioning .= "Executive functions are a collection of processes that are responsible for guiding, directing, and managing cognitive, emotional, and behavioral functions, particularly during active, novel problem solving. In order to obtain information regarding {$examinee_name}'s executive functioning at school and at home, the Behavior Rating Inventory of Executive Function, Second Edition (BRIEF-2) was completed by {$his_her} {$infor_dropbox1}, {$informer_title0}, {$infor_dropbox2}, {$informer_title1} and {$infor_dropbox3}, {$informer_title2}. The BRIEF-2 is designed for individuals ages 5 to 18 for whom there may be a concern about self-regulation. BRIEF-2 T-scores have a mean of 50 and a standard deviation of 10. BRIEF-2 scores 60-64 are considered mildly elevated, scores 65-69 are considered potentially clinically elevated and scores of 70 and above are typically considered clinically elevated or clinically significant. {$examinee_name_2}'s functioning was assessed through three broad Composites: Behavior Regulation, Emotion Regulation, and Cognitive Regulation. The BRIEF-2 also provides a Global Executive Composite (GEC) score that serves as an overall estimate of executive functioning. {$examinee_name_2}'s GEC was within the ";

            if ( $rawScores['gec'][1] == $rawScores['gec'][2] ) {
                $executiveFunctioning .= $ranges['gec'][1] . " range by both {$informer_title0} and {$informer_title1}. ";
            } else {
                $executiveFunctioning .= $ranges['gec'][1] . " range by {$informer_title0} and within the " . $ranges['gec'][2] . " range by {$his_her} {$informer_title1}.";
            }
            $executiveFunctioning .= " Additionally, {$informer_title2} rated {$examinee_name}'s overall level of executive functioning to be within the " . $ranges['gec'][3] . ' range.';

            $behavioral_regulation_index .= "Behavior Regulation Index: This composite represents a student's ability to regulate and monitor behavior effectively. {$examinee_name_2}'s overall level of behavior regulation problems was rated to be in the ";

            if ( $rawScores['bri'][1] == $rawScores['bri'][2] ) {
                $behavioral_regulation_index .= $ranges['bri'][1] . " range by {$informer_title0} and {$informer_title1}. ";
            } else {
                $behavioral_regulation_index .= $ranges['bri'][1] . " range by {$informer_title0} and within the " . $ranges['bri'][2] . " range by {$informer_title1}.";
            }

            if ( $rawScores['inhibit'][1] == $rawScores['inhibit'][2] && $rawScores['selfmonitor'][1] == $rawScores['selfmonitor'][2] ) {
                $behavioral_regulation_index .= " Specifically, {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to control {$his_her} impulses (inhibit) within the " . $ranges['inhibit'][1] . " range, and {$his_her} ability to self-monitor to be in the " . $ranges['selfmonitor'][1] . ' range. ';
            } else {
                $behavioral_regulation_index .= " Specifically, {$informer_title0} rated {$examinee_name}'s ability to control {$his_her} impulses (inhibit) within the " . $ranges['inhibit'][1] . " range, and {$his_her} ability to self-monitor in the " . $ranges['selfmonitor'][1] . " range. However, {$informer_title1} rated the Inhibit scale to be within the " . $ranges['inhibit'][2] . " range and the Self-Monitor scale to be within the " . $ranges['selfmonitor'][2] . ' range. ';
            }

            if ( $ranges['inhibit'][3] !== 'average' || $ranges['selfmonitor'][3] !== 'average' ) {
                $behavioral_regulation_index .= " Additionally, {$informer_title2} rated the Inhibit scale in the " . $ranges['inhibit'][3] . ' range and the Self-Monitor scale in the ' . $ranges['selfmonitor'][3] . ' range.';
            }

            $emotional_regulation_index .= "Emotional Regulation Index: The Emotion Regulation Index (ERI) represents a student's ability to regulate emotional responses including responding to changing situations. {$examinee_name_2}'s overall level of emotional regulation skills were considered ";

            if ( $rawScores['eri'][1] == $rawScores['eri'][2] ) {
                $emotional_regulation_index .= ' to be within the ' . $ranges['eri'][1] . " range by both {$informer_title0} and {$informer_title1}. ";
            } else {
                $emotional_regulation_index .= ' to be within the ' . $ranges['eri'][1] . " range by {$informer_title0} and the " . $ranges['eri'][2] . " range by {$informer_title1}. ";
            }

            if ( $rawScores['shift'][1] == $rawScores['shift'][2] && $rawScores['emotional_control'][1] == $rawScores['emotional_control'][2] ) {
                $emotional_regulation_index .= " Specifically, both {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to transition (Shift) to be within the " . $ranges['shift'][1] . " range, and ability to regulate {$his_her} emotions (Emotional Control) to be within the " . $ranges['emotional_control'][1] . ' range. ';
            } else {
                $emotional_regulation_index .= " Specifically, {$informer_title0} rated {$examinee_name}'s ability to transition (Shift) to be within the " . $ranges['shift'][1] . " range, and ability to regulate {$his_her} emotions to be within the " . $ranges['emotional_control'][1] . " range. However, {$informer_title1} indicated " . $ranges['shift'][2] . ' functioning on the Shift scale and ' . $ranges['emotional_control'][2] . ' functioning on the Emotional Control scale. ';
            }

            if ( $ranges['shift'][3] !== 'average' || $ranges['emotional_control'][3] !== 'average' ) {
                $emotional_regulation_index .= " Additionally, {$informer_title2} rated the Shift scale to be within the " . $ranges['shift'][3] . ' range and the Emotional Control Scale within the ' . $ranges['emotional_control'][3] . ' range. ';
            }

            $cognitive_regulation_index .= "Cognitive Regulation Index: The Cognitive Regulation Index (CRI) represents a student's ability to control and manage cognitive processes and problem-solve effectively. {$examinee_name_2}'s overall CRI ability was rated to be within the ";

            if ( $rawScores['cri'][1] == $rawScores['cri'][2] ) {
                $cognitive_regulation_index .= $ranges['cri'][1] . " range by {$informer_title0} and {$informer_title1}. ";
            } else {
                $cognitive_regulation_index .= $ranges['cri'][1] . " range by {$informer_title0} and within the " . $ranges['cri'][2] . " range by {$informer_title1}. ";
            }

            if ( $rawScores['initiate'][1] == $rawScores['initiate'][2] && $rawScores['working_memory'][1] == $rawScores['working_memory'][2] ) {
                $cognitive_regulation_index .= "Specifically, both {$informer_title0} and {$informer_title1} rated {$examinee_name}'s ability to begin an activity or generate ideas (Initiate) to be within the " . $ranges['initiate'][1] . " range and {$his_her} ability to hold information in {$his_her} mind to be within the " . $ranges['working_memory'][1] . ' range. ';
            } else {
                $cognitive_regulation_index .= "{$informer_title0} rated {$examinee_name}'s ability to begin an activity or generate ideas (Initiate) to be within the " . $ranges['initiate'][1] . " range and {$his_her} ability to hold information in {$his_her} mind (Working Memory) to be within the " . $ranges['working_memory'][1] . " range. Whereas {$informer_title1} rated the Initiate scale within the " . $ranges['initiate'][2] . " range and the Working Memory scale within the " . $ranges['working_memory'][2] . ' range. ';
            }

            if ( $ranges['initiate'][3] !== 'average' || $ranges['working_memory'][3] !== 'average' ) {
                $cognitive_regulation_index .= " Additionally, {$informer_title2} rated the Initiate scale within the " . $ranges['initiate'][3] . ' range and the Working Memory scale within the ' . $ranges['working_memory'][3] . ' range. ';
            }

            if ( $rawScores['planorganize'][1] == $rawScores['planorganize'][2] && $rawScores['taskmonitor'][1] == $rawScores['taskmonitor'][2] && $rawScores['organization_of_materials'][1] == $rawScores['organization_of_materials'][2] ) {
                $cognitive_regulation_index .= " They also both rated {$examinee_name}'s ability to set goals to be within the " . $ranges['planorganize'][1] . " range, ability to check {$his_her} work to be within the " . $ranges['taskmonitor'][1] . " range, and ability to keep {$his_her} work/play area clean and orderly to be within the " . $ranges['organization_of_materials'][1] . ' range.';
            } else {
                $cognitive_regulation_index .= " Additionally, {$informer_title0} rated {$examinee_name}'s ability to set goals (Plan/Organize) to be within the " . $ranges['planorganize'][1] . " range, ability to check {$his_her} work (Task-Monitor) to be within the " . $ranges['taskmonitor'][1] . " range, and ability to keep {$his_her} work/play area clean and orderly (Organization of Materials) to be within the " . $ranges['organization_of_materials'][1] . " range. {$informer_title1} indicated " . $ranges['planorganize'][2] . ' functioning for the Plan/Organize scale, ' . $ranges['taskmonitor'][2] . ' functioning for the Task-Monitoring scale, and ' . $ranges['organization_of_materials'][2] . ' functioning for the Organization of Materials scale. ';
            }

            if ( $ranges['planorganize'][3] !== 'average' || $ranges['taskmonitor'][3] !== 'average' || $ranges['organization_of_materials'][3] !== 'average' ) {
                $cognitive_regulation_index .= " Furthermore, {$informer_title2} reported " . $ranges['planorganize'][3] . ' functioning for the Plan/Organize scale, ' . $ranges['taskmonitor'][3] . ' functioning for the Task-Monitoring scale, and ' . $ranges['organization_of_materials'][3] . ' functioning for the Organization of Materials scale. ';
            }

            // Validity scales
            $brief_2_rating_salses .= "The BRIEF-2 Rating Scales assess a rater's response pattern to determine how results should be interpreted. ";
            if ( $negOne === 'acceptable' && $incOne === 'acceptable' && $infOne === 'acceptable' ) {
                $brief_2_rating_salses .= "{$informer_title0}'s Inconsistency, Infrequency, and Negativity indices were all considered to be acceptable, indicating that these ratings are likely a valid representation of {$examinee_name}'s current behavioral functioning at this time. ";
            } else {
                $brief_2_rating_salses .= "While {$informer_title0}'s ratings were considered acceptable for appropriate frequency and consistency, the Negativity scale indicates responses that were excessively negative, and the validity of these scores was in the {$negOne} range.";
            }

            if ( $negTwo === $negOne && $infTwo === $infOne && $incTwo === $incOne ) {
                $brief_2_rating_salses .= ' Likewise, ';
            } else {
                $brief_2_rating_salses .= ' Additionally, ';
            }

            if ( $negTwo === 'acceptable' && $infTwo === 'acceptable' && $incTwo === 'acceptable' ) {
                $brief_2_rating_salses .= "{$informer_title1}'s Inconsistency, Infrequency, and Negativity indices were considered to be acceptable. ";
            }

            if ( $negThree === 'acceptable' && $incThree === 'acceptable' && $infThree === 'acceptable' ) {
                $brief_2_rating_salses .= "Moreover, {$informer_title2}'s response pattern was considered to portray a valid representation of {$examinee_name}'s current behavioral/executive functioning at this time.";
            } else {
                $brief_2_rating_salses .= "Moreover, {$informer_title2}'s response pattern was NOT considered to portray a valid representation of {$examinee_name}'s current behavioral/executive functioning at this time; therefore {$informer_title2}'s scores should be interpreted with caution.";
            }

            if ( $infOne !== 'acceptable' || $infTwo !== 'acceptable' || $infThree !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Infrequency index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N). ';
            }
            if ( $incOne !== 'acceptable' || $incTwo !== 'acceptable' || $incThree !== 'acceptable' ) {
                $brief_2_rating_salses .= ' The Inconsistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way. ';
            }
        }

        $html  = "<p class='executiveFunctioning-header'><strong>Executive Functioning</strong></p>";
        $html .= "<p class='executiveFunctioning'>" . trim( $executiveFunctioning ) . '</p>';
        $html .= "<p class='behavioral_regulation_index'>" . trim( $behavioral_regulation_index ) . '</p>';
        $html .= "<p class='emotional_regulation_index'>" . trim( $emotional_regulation_index ) . '</p>';
        $html .= "<p class='cognitive_regulation_index'>" . trim( $cognitive_regulation_index ) . '</p>';
        $html .= "<p class='brief_2_rating_salses'>" . trim( $brief_2_rating_salses ) . '</p>';

        return [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $html,
        ];
    }

    /**
     * Intelligently extract score value for a scale and rater from input data,
     * supporting exact keys, 0-indexed/1-indexed conventions, word numbers (_one, _two),
     * parent composite suffixes (e.g. initiate_1_cri), and full descriptive HTML input IDs (e.g. global_executive_composite_gec_1).
     */
    private function extractRaterScoreValue( array $inputData, string $scaleKey, int $raterIdx ): mixed {
        $r0 = $raterIdx - 1;
        $candidateKeys = [
            "{$scaleKey}_{$raterIdx}",
            "{$scaleKey}_{$r0}",
        ];

        // Specific aliases for common templates (e.g. BRIEF-2, BASC-3, SRS-2)
        $aliasMap = [
            'gec' => [ 'global_executive_composite_gec', 'global_executive_composite', 'gec_composite' ],
            'bri' => [ 'behavioral_regulation_index_bri', 'behavior_regulation_index_bri', 'behavioral_regulation_index', 'behavior_regulation_index' ],
            'eri' => [ 'emotional_regulation_index_eri', 'emotion_regulation_index_eri', 'emotional_regulation_index', 'emotion_regulation_index' ],
            'cri' => [ 'cognitive_regulation_index_cri', 'cognitive_regulation_index' ],
            'initiate' => [ 'initiate_cri', 'cri_initiate' ],
            'adaptive_skills' => [ 'adaptive_skills_tscores_reversed', 'adaptive_skills_composite' ],
            'behavioral_symptoms' => [ 'behavioral_symptoms_index', 'bsi' ],
            'externalizing_problems' => [ 'externalizing_problems_index', 'externalizing' ],
            'internalizing_problems' => [ 'internalizing_problems_index', 'internalizing' ],
            'total_sensory' => [ 'total_sensory_system_score', 'total_sensory_system' ],
            'restricted_interests' => [ 'restricted_interests_and_repetitive_behavior' ],
        ];

        // Word numbers for templates like SRS-2 (e.g. total_score_one, total_score_two)
        $words = [ 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five' ];
        if ( isset( $words[ $raterIdx ] ) ) {
            $candidateKeys[] = "{$scaleKey}_{$words[ $raterIdx ]}";
        }

        if ( isset( $aliasMap[ $scaleKey ] ) ) {
            foreach ( $aliasMap[ $scaleKey ] as $alias ) {
                $candidateKeys[] = "{$alias}_{$raterIdx}";
                $candidateKeys[] = "{$alias}_{$r0}";
                if ( isset( $words[ $raterIdx ] ) ) {
                    $candidateKeys[] = "{$alias}_{$words[ $raterIdx ]}";
                }
            }
        }

        if ( 1 === $raterIdx ) {
            $candidateKeys[] = $scaleKey;
            if ( isset( $aliasMap[ $scaleKey ] ) ) {
                foreach ( $aliasMap[ $scaleKey ] as $alias ) {
                    $candidateKeys[] = $alias;
                }
            }
        }

        foreach ( $candidateKeys as $k ) {
            if ( isset( $inputData[ $k ] ) && '' !== $inputData[ $k ] ) {
                return $inputData[ $k ];
            }
            if ( isset( $inputData['scores'][ $k ] ) && '' !== $inputData['scores'][ $k ] ) {
                return $inputData['scores'][ $k ];
            }
        }

        // Fuzzy suffix/infix search across all input keys (e.g. initiate_1_cri, global_executive_composite_gec_1, communication_domain2)
        $patterns = [ preg_quote( $scaleKey, '/' ) ];
        if ( isset( $aliasMap[ $scaleKey ] ) ) {
            foreach ( $aliasMap[ $scaleKey ] as $alias ) {
                $patterns[] = preg_quote( $alias, '/' );
            }
        }

        $numSuffixes = [ (string) $raterIdx, (string) $r0 ];
        if ( isset( $words[ $raterIdx ] ) ) {
            $numSuffixes[] = $words[ $raterIdx ];
        }
        $numRegex = '(' . implode( '|', $numSuffixes ) . ')';

        foreach ( $patterns as $p ) {
            foreach ( $inputData as $k => $v ) {
                if ( '' === $v || null === $v || ! is_numeric( $v ) ) {
                    continue;
                }
                if ( preg_match( '/(^|_)' . $p . '(_?' . $numRegex . ')(\b|_|$)/i', $k ) ) {
                    return $v;
                }
            }
        }

        return null;
    }
}

