<?php
/**
 * Unit Tests: All 5 Clinical Archetype Processors + wp_kses Sanitization
 *
 * @package ClicReport\AssessmentEngine\Tests\Unit
 */

declare(strict_types=1);

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;
use ClicReport\AssessmentEngine\Services\Engine\ArchetypeFactory;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;

final class ArchetypeProcessorsTest {

    public function run(): void {
        global $wpdb;

        $templateRepo  = new TemplateRepository( $wpdb );
        $scaleRepo     = new ScaleRepository( $wpdb );
        $narrativeRepo = new NarrativeRuleRepository( $wpdb );

        $samples = [
            't_score'            => [
                'slug'  => 'report-basc-3-social-emotional',
                'input' => [
                    'first_name'    => 'Jordan',
                    'gender'        => 'they/them',
                    '0'             => 'Two-row',
                    'hyperactivity_0' => 74,
                    'depression_0'  => 65,
                ],
            ],
            't_score_self_report' => [
                'slug'  => 'report-basc-3-self-report',
                'input' => [
                    'first_name'            => 'Taylor',
                    'gender'                => 'male',
                    'school_problems_1'     => 50,
                    'attitude_to_school_1'  => 50,
                    'attitude_to_teachers_1'=> 50,
                    'sensation_seeking_1'   => 50,
                    'internalizing_problems_1' => 50,
                    'atypicality_1'         => 50,
                    'locus_of_control_1'    => 50,
                    'somatization_1'        => 50,
                    'social_stress_1'       => 50,
                    'anxiety_1'             => 50,
                    'depression_1'          => 50,
                    'sense_of_inadequacy_1' => 50,
                    'inattention_hyperactivity_1' => 50,
                    'attention_problems_1'  => 50,
                    'hyperactivity_1'       => 50,
                    'personal_adjustment_1' => 50,
                    'relations_with_parents_1' => 50,
                    'interpersonal_relations_1' => 50,
                    'selfesteem_1'          => 50,
                    'selfreliance_1'        => 50,
                    'selfre_1'              => 50,
                ],
            ],
            'dual_standard_scaled' => [
                'slug'  => 'report-wisc-v',
                'input' => [
                    'first_name' => 'Maya',
                    'gender'     => 'she/her',
                    'fsiq_0'     => 108,
                ],
            ],
            'clinical_cutoff'  => [
                'slug'  => 'report-scq',
                'input' => [
                    'first_name'   => 'Liam',
                    'gender'       => 'he/him',
                    'total_score_0' => 19,
                ],
            ],
            'static_reference' => [
                'slug'  => 'abasdescriptions',
                'input' => [
                    'first_name' => 'Elena',
                    'gender'     => 'she/her',
                ],
            ],
            'qualitative_matrix' => [
                'slug'  => 'eligibilitysld',
                'input' => [
                    'first_name' => 'Noah',
                    'gender'     => 'he/him',
                    'decisions'  => [
                        'exclusionary_factors' => 'ruled_out',
                    ],
                ],
            ],
        ];

        foreach ( $samples as $archetype => $spec ) {
            $tpl = $templateRepo->findBySlug( $spec['slug'] );
            CraeAssert::notEmpty( $tpl, 'Loaded seed template for ' . $archetype );

            $sections = $templateRepo->getSections( (int) $tpl['id'] );
            $scales   = $scaleRepo->getByTemplate( (int) $tpl['id'] );

            $processor = ArchetypeFactory::create( $archetype );
            $output    = $processor->process( $tpl, $sections, $scales, $spec['input'], $narrativeRepo );

            CraeAssert::true( array_key_exists( 'narrative_html', $output ), $archetype . ' returns narrative_html' );
            CraeAssert::notEmpty( $output['narrative_html'], $archetype . ' narrative_html non-empty' );
            CraeAssert::true( array_key_exists( 'scores_evaluated', $output ), $archetype . ' returns scores_evaluated' );
        }

        // Verify XSS sanitization strips dangerous tags & event handlers while preserving clinical structure.
        $dirty     = '<div class="clinical-section"><p>Score for <script>alert(1)</script>Alex</p><span onclick="evil()">Elevated</span></div>';
        $sanitized = NarrativeInterpolator::sanitizeClinicalHtml( $dirty );
        CraeAssert::notContains( '<script>', $sanitized, 'sanitizeClinicalHtml removes script tag' );
        CraeAssert::notContains( 'onclick=', $sanitized, 'sanitizeClinicalHtml removes inline JS handlers' );
        CraeAssert::contains( '<div class="clinical-section">', $sanitized, 'sanitizeClinicalHtml preserves clinical container' );

        // Verify BRIEF-2 processes raw HTML input IDs (including initiate_1_cri and global_executive_composite_gec_1)
        $brief2Tpl = $templateRepo->findBySlug( 'report-brief-2' );
        if ( ! empty( $brief2Tpl ) ) {
            $sections = $templateRepo->getSections( (int) $brief2Tpl['id'] );
            $scales   = $scaleRepo->getByTemplate( (int) $brief2Tpl['id'] );
            $brief2Input = [
                'first_name' => 'John',
                'gender'     => 'he/him',
                'behavioral_regulation_index_bri_1' => 55,
                'inhibit_1' => 60,
                'selfmonitor_1' => 52,
                'emotional_regulation_index_eri_1' => 58,
                'shift_1' => 54,
                'emotional_control_1' => 62,
                'cognitive_regulation_index_cri_1' => 65,
                'initiate_1_cri' => 68,
                'working_memory_1' => 70,
                'planorganize_1' => 66,
                'taskmonitor_1' => 64,
                'organization_of_materials_1' => 59,
                'global_executive_composite_gec_1' => 67,
            ];
            $processor = ArchetypeFactory::create( 't_score' );
            $output    = $processor->process( $brief2Tpl, $sections, $scales, $brief2Input, $narrativeRepo );
            CraeAssert::true( array_key_exists( 'initiate', $output['scores_evaluated'] ), 'BRIEF-2 evaluates initiate from initiate_1_cri' );
            CraeAssert::true( array_key_exists( 'gec', $output['scores_evaluated'] ), 'BRIEF-2 evaluates gec from global_executive_composite_gec_1' );
            CraeAssert::same( 68.0, (float) $output['scores_evaluated']['initiate']['raters'][1]['score'], 'Initiate score correctly extracted' );
            CraeAssert::same( 67.0, (float) $output['scores_evaluated']['gec']['raters'][1]['score'], 'GEC score correctly extracted' );
            CraeAssert::contains( 'Executive Functioning', $output['narrative_html'], 'BRIEF-2 includes Executive Functioning header' );
            CraeAssert::contains( 'Behavior Regulation Index:', $output['narrative_html'], 'BRIEF-2 includes Behavior Regulation Index synthesis' );
            CraeAssert::contains( 'Emotional Regulation Index:', $output['narrative_html'], 'BRIEF-2 includes Emotional Regulation Index synthesis' );
            CraeAssert::contains( 'Cognitive Regulation Index:', $output['narrative_html'], 'BRIEF-2 includes Cognitive Regulation Index synthesis' );
            CraeAssert::contains( 'The BRIEF-2 Rating Scales assess a rater\'s response pattern', $output['narrative_html'], 'BRIEF-2 includes validity scales interpretation' );
        }
    }
}
