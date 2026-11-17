<?php
/**
 * Unit Tests: Psychometric Scoring & Normative Lookup Profiles
 *
 * @package ClicReport\AssessmentEngine\Tests\Unit
 */

declare(strict_types=1);

use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

final class AssessmentScorerTest {

    public function run(): void {
        // 1. BASC-3 / BRIEF-2 / Conners Standard Clinical T-Score
        $high = AssessmentScorer::classify( 74, AssessmentScorer::SCALE_CLINICAL, null, 'basc_standard' );
        CraeAssert::same( 'Clinically Significant', $high['range'], 'BASC clinical T=74 range' );
        CraeAssert::same( '**', $high['stars'], 'BASC clinical T=74 stars' );
        CraeAssert::true( $high['is_elevated'], 'BASC clinical T=74 elevated' );

        $avg = AssessmentScorer::classify( 52, AssessmentScorer::SCALE_CLINICAL, null, 'basc_standard' );
        CraeAssert::same( 'Average', $avg['range'], 'BASC clinical T=52 range' );
        CraeAssert::false( $avg['is_elevated'], 'BASC clinical T=52 not elevated' );

        // 2. Inverse Adaptive Behavior T-Score
        $adaptiveLow = AssessmentScorer::classify( 28, AssessmentScorer::SCALE_ADAPTIVE );
        CraeAssert::same( 'Clinically Significant', $adaptiveLow['range'], 'Adaptive T=28 range' );
        CraeAssert::true( $adaptiveLow['is_elevated'], 'Adaptive T=28 flagged' );

        $adaptiveStr = AssessmentScorer::classify( 64, AssessmentScorer::SCALE_ADAPTIVE );
        CraeAssert::same( 'High / Strength', $adaptiveStr['range'], 'Adaptive T=64 strength' );
        CraeAssert::false( $adaptiveStr['is_elevated'], 'Adaptive T=64 not elevated' );

        // 3. Specialized Clinical Profiles (SRS-2, EDDT, MASC-2, SPM-2)
        CraeAssert::same( 'Severe', AssessmentScorer::classify( 78, 'clinical', null, 'srs2_severity' )['range'], 'SRS-2 T=78' );
        CraeAssert::same( 'Moderate', AssessmentScorer::classify( 68, 'clinical', null, 'srs2_severity' )['range'], 'SRS-2 T=68' );
        CraeAssert::same( 'Very High Clinical', AssessmentScorer::classify( 82, 'clinical', null, 'eddt_clinical' )['range'], 'EDDT T=82' );
        CraeAssert::same( 'Elevated', AssessmentScorer::classify( 67, 'clinical', null, 'masc2_anxiety' )['range'], 'MASC-2 T=67' );
        CraeAssert::same( 'Definite Dysfunction', AssessmentScorer::classify( 72, 'clinical', null, 'spm2_sensory' )['range'], 'SPM-2 T=72' );

        // 4. Dual Standard (M=100, SD=15), Scaled (M=10, SD=3), V-Scale, SAED Pathology & Cutoff
        $std = AssessmentScorer::classify( 100, AssessmentScorer::SCALE_STANDARD );
        CraeAssert::same( '50', (string) $std['percentile'], 'SS=100 50th percentile' );

        $stdLow = AssessmentScorer::classify( 78, AssessmentScorer::SCALE_STANDARD );
        CraeAssert::true( $stdLow['is_elevated'], 'SS=78 flagged below average cutoff' );

        $scaled = AssessmentScorer::classify( 10, AssessmentScorer::SCALE_SCALED );
        CraeAssert::same( '50', (string) $scaled['percentile'], 'Scaled=10 50th percentile' );

        $vScale = AssessmentScorer::classify( 11, AssessmentScorer::SCALE_V_SCALE );
        CraeAssert::true( $vScale['is_elevated'], 'Vineland v-scale <= 12 flagged' );

        $saed = AssessmentScorer::classify( 15, AssessmentScorer::SCALE_SCALED_PATHOLOGY );
        CraeAssert::true( $saed['is_elevated'], 'SAED pathology >= 14 indicative' );

        $cutoff = AssessmentScorer::classify( 18, AssessmentScorer::SCALE_CUTOFF, 15.0 );
        CraeAssert::true( $cutoff['is_elevated'], 'SCQ cutoff 18 >= 15 meets threshold' );
    }
}

