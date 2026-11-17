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
                    $rawVal = $inputData[ "{$scaleKey}_{$r}" ]
                        ?? ( $inputData[ "{$scaleKey}_" . ( $r - 1 ) ]
                        ?? ( 1 === $r ? ( $inputData[ $scaleKey ] ?? ( $inputData['scores'][ $scaleKey ] ?? null ) ) : null ) );
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

        if ( empty( $scales ) && ! empty( $inputData['scores'] ) && is_array( $inputData['scores'] ) ) {
            if ( ! empty( $templateRow['intro_narrative'] ) ) {
                $narrativeHtml .= "<p class='Social-body_n'>" . NarrativeInterpolator::render( $templateRow['intro_narrative'], $context ) . '</p>';
            }
            foreach ( $inputData['scores'] as $key => $val ) {
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
            $r1_study = $scaleRanges['study_skills'][1] ?? 'Average';
            $r2_study = $scaleRanges['study_skills'][2] ?? 'Average';
            $adapt_study = "";
            if ( ! $isPreschool && $activeRatersCount >= 2 ) {
                $adapt_study = " Additionally, {$inf_one} rated the area of Study Skills to be within the {$r1_study} range, and {$inf_two} rated {$r2_study} concerns for study skills.";
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
                return "by both {$inf_one} and {$inf_two}.";
            }
            return "by {$inf_one} but within the {$r2} range by {$inf_two}.";
        }

        // 3+ raters
        if ( $r1 === $r2 && $r2 === $r3 ) {
            if ( in_array( $sKey, [ 'somatization', 'depression', 'anxiety', 'conduct_problems', 'withdrawal', 'atypicality', 'behavioral_symptoms' ], true ) ) {
                return "by all evaluators on this measure.";
            }
            if ( 'attention_problems' === $sKey || 'Attention' === $sKey ) {
                return "by all evaluators on measures of attention,";
            }
            $allAvg = ( 'Average' === $r1 && 'Average' === $r2 && 'Average' === $r3 );
            return $allAvg ? "by all evaluators," : "by all evaluators.";
        }

        return "by {$inf_one}, within the {$r2} range by {$inf_two}, and within the {$r3} range by {$inf_thr}.";
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
            'externalizing_problems' => " suggesting that {$his_her} externalizing behaviors are consistent across settings.",
            'hyperactivity'          => " suggesting that {$his_her} hyperactive behaviors are consistent across settings.",
            'aggression'             => " suggesting that {$his_her} aggression behaviors are consistent across settings.",
            'internalizing_problems' => " indicating that {$possName} internalizing behaviors are consistent across settings.",
            'attention_problems',
            'Attention'              => " indicating that these behaviors are seen across settings.",
            'behavioral_symptoms'    => " suggesting that these behaviors are observable across different environments.",
            'atypicality'            => " suggesting that these behaviors are observable across different settings.",
            'withdrawal'             => " suggesting that these behaviors are observable across different settings.",
            default                  => '',
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
}

