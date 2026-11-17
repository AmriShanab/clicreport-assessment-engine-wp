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
    }
}
