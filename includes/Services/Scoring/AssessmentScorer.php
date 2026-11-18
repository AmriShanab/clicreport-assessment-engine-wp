<?php
/**
 * AssessmentScorer
 * 
 * Universal Psychometric Scoring & Normative Lookup Engine (v1.0 - All 201 Templates).
 * Centralizes all mathematical and empirical transformations across all 5 Archetypes:
 *   - Archetype 1: Normative T-Scores (Mean = 50, SD = 10)
 *       * Profiles: 'basc_standard', 'srs2_severity', 'eddt_clinical', 'masc2_anxiety', 'spm2_sensory'
 *   - Archetype 2: Dual Standard Scores (Mean = 100, SD = 15), Scaled Scores (Mean = 10, SD = 3),
 *       * Vineland-3 V-Scale Scores (Mean = 15, SD = 3)
 *       * SAED-3 Pathology Scaled Scores (Mean = 10, SD = 3, >= 14 Indicative)
 *       * XBASS Scaled-to-Standard Score Converter
 *   - Archetype 3: Clinical Cutoff Thresholds (ADI-R, ADOS-2 Mod 1-3, SCQ >= 15, Vanderbilt-2)
 *   - Archetype 5: Kennedy Axis V GAF Averaging (0-100) & Ordinal Preference Ranking (Dunn-Rankin)
 *   - Confidence Interval Computation (90% and 95%)
 */

namespace ClicReport\AssessmentEngine\Services\Scoring;

use ClicReport\AssessmentEngine\Services\Narrative\ReportGrammar;

defined( 'ABSPATH' ) || exit;
class AssessmentScorer {
    public const SCALE_CLINICAL         = 'clinical';
    public const SCALE_ADAPTIVE         = 'adaptive';
    public const SCALE_STANDARD         = 'standard';
    public const SCALE_SCALED           = 'scaled';
    public const SCALE_V_SCALE          = 'v_scale';
    public const SCALE_SCALED_PATHOLOGY = 'scaled_pathology';
    public const SCALE_CUTOFF           = 'cutoff';

    /**
     * Empirical Standard Score (40-160, M=100, SD=15) to Percentile Rank Lookup Table.
     */
    public const STANDARD_SCORE_PERCENTILES = [
        40 => '<0.1', 41 => '<0.1', 42 => '<0.1', 43 => '<0.1', 44 => '<0.1', 45 => '<0.1',
        46 => '<0.1', 47 => '<0.1', 48 => '<0.1', 49 => '<0.1', 50 => '<0.1', 51 => '0.1',
        52 => '0.1',  53 => '0.1',  54 => '0.1',  55 => '0.1',  56 => '0.2',  57 => '0.2',
        58 => '0.3',  59 => '0.3',  60 => '0.4',  61 => '0.5',  62 => '1',    63 => '1',
        64 => '1',    65 => '1',    66 => '1',    67 => '1',    68 => '2',    69 => '2',
        70 => '2',    71 => '3',    72 => '3',    73 => '4',    74 => '4',    75 => '5',
        76 => '5',    77 => '6',    78 => '7',    79 => '8',    80 => '9',    81 => '10',
        82 => '12',   83 => '13',   84 => '14',   85 => '16',   86 => '18',   87 => '19',
        88 => '21',   89 => '23',   90 => '25',   91 => '27',   92 => '30',   93 => '32',
        94 => '34',   95 => '37',   96 => '39',   97 => '42',   98 => '45',   99 => '47',
        100 => '50',  101 => '53',  102 => '55',  103 => '58',  104 => '61',  105 => '63',
        106 => '66',  107 => '68',  108 => '70',  109 => '73',  110 => '75',  111 => '77',
        112 => '79',  113 => '81',  114 => '82',  115 => '84',  116 => '86',  117 => '87',
        118 => '88',  119 => '90',  120 => '91',  121 => '92',  122 => '93',  123 => '94',
        124 => '95',  125 => '95',  126 => '96',  127 => '96',  128 => '97',  129 => '97',
        130 => '98',  131 => '98',  132 => '98',  133 => '99',  134 => '99',  135 => '99',
        136 => '99',  137 => '99',  138 => '99',  139 => '99.5',140 => '99.6',141 => '99.7',
        142 => '99.7',143 => '99.8',144 => '99.8',145 => '99.9',146 => '99.9',147 => '99.9',
        148 => '99.9',149 => '99.9',150 => '>99.9',151 => '>99.9',152 => '>99.9',153 => '>99.9',
        154 => '>99.9',155 => '>99.9',156 => '>99.9',157 => '>99.9',158 => '>99.9',159 => '>99.9',
        160 => '>99.9'
    ];

    /**
     * Empirical Subtest Scaled Score (1-19, M=10, SD=3) to Percentile Rank Lookup Table.
     */
    public const SCALED_SCORE_PERCENTILES = [
        1  => '0.1',
        2  => '0.4',
        3  => '1',
        4  => '2',
        5  => '5',
        6  => '9',
        7  => '16',
        8  => '25',
        9  => '37',
        10 => '50',
        11 => '63',
        12 => '75',
        13 => '84',
        14 => '91',
        15 => '95',
        16 => '98',
        17 => '99',
        18 => '99.6',
        19 => '99.9',
    ];

    /**
     * Empirical T-Score (1-99, M=50, SD=10) to Percentile Rank Lookup Table.
     * Used across Archetype 1 & 7 (BOT-2, DAS-II subtests, RIAS-2 subtests, WIAT-3 subtests, EDDT).
     */
    public const T_SCORE_PERCENTILES = [
        1 => '<1', 2 => '<1', 3 => '<1', 4 => '<1', 5 => '<1',
        6 => '<1', 7 => '<1', 8 => '<1', 9 => '<1', 10 => '<1',
        11 => '<1', 12 => '<1', 13 => '<1', 14 => '<1', 15 => '<1',
        16 => '<1', 17 => '<1', 18 => '<1', 19 => '<1', 20 => '<1',
        21 => '<1', 22 => '<1', 23 => '<1', 24 => '<1', 25 => '1',
        26 => '1', 27 => '1', 28 => '1', 29 => '1', 30 => '2',
        31 => '3', 32 => '4', 33 => '5', 34 => '6', 35 => '7',
        36 => '8', 37 => '9', 38 => '12', 39 => '14', 40 => '16',
        41 => '18', 42 => '21', 43 => '24', 44 => '28', 45 => '31',
        46 => '34', 47 => '39', 48 => '43', 49 => '47', 50 => '50',
        51 => '54', 52 => '58', 53 => '62', 54 => '68', 55 => '70',
        56 => '73', 57 => '75', 58 => '79', 59 => '82', 60 => '84',
        61 => '86', 62 => '88', 63 => '90', 64 => '92', 65 => '94',
        66 => '95', 67 => '95', 68 => '96', 69 => '97', 70 => '98',
        71 => '98', 72 => '98', 73 => '99', 74 => '99', 75 => '>99',
        76 => '>99', 77 => '>99', 78 => '>99', 79 => '>99', 80 => '>99',
        81 => '>99', 82 => '>99', 83 => '>99', 84 => '>99', 85 => '>99',
        86 => '>99', 87 => '>99', 88 => '>99', 89 => '>99', 90 => '>99',
        91 => '>99', 92 => '>99', 93 => '>99', 94 => '>99', 95 => '>99',
        96 => '>99', 97 => '>99', 98 => '>99', 99 => '>99',
    ];

    /**
     * Universal dispatcher to classify any score according to its scale_type.
     *
     * @param mixed $score
     * @param string $scaleType 'clinical', 'adaptive', 'standard', 'scaled', 'v_scale', 'scaled_pathology', 'cutoff'
     * @param float|null $cutoffThreshold Optional threshold for cutoff or custom weakness rules
     * @param string $profile Optional T-score profile ('basc_standard', 'srs2_severity', 'eddt_clinical', 'masc2_anxiety', 'spm2_sensory')
     * @return array
     */
    public static function classify(
        $score,
        string $scaleType = self::SCALE_CLINICAL,
        ?float $cutoffThreshold = null,
        string $profile = 'basc_standard'
    ): array {
        return match ($scaleType) {
            self::SCALE_CLINICAL,
            self::SCALE_ADAPTIVE         => self::classifyTScoreByProfile($score, $scaleType, $profile),
            self::SCALE_STANDARD         => self::classifyStandardScore($score, $cutoffThreshold ?? 85.0),
            self::SCALE_SCALED           => self::classifyScaledScore($score, $cutoffThreshold ?? 6.0),
            self::SCALE_V_SCALE          => self::classifyVScaleScore($score),
            self::SCALE_SCALED_PATHOLOGY => self::classifyPathologyScaledScore($score),
            self::SCALE_CUTOFF           => self::classifyClinicalCutoff($score, $cutoffThreshold ?? 10.0),
            default                      => self::classifyTScore($score, self::SCALE_CLINICAL),
        };
    }

    /**
     * Classifies an Archetype 1 Normative T-Score (Mean = 50, SD = 10) using the standard BASC/BRIEF/Conners profile.
     *
     * @param float|int|string|null $score
     * @param string $scaleType 'clinical' or 'adaptive'
     * @return array
     */
    public static function classifyTScore($score, string $scaleType = self::SCALE_CLINICAL): array {
        return self::classifyTScoreByProfile($score, $scaleType, 'basc_standard');
    }

    /**
     * Classifies a T-Score according to the assessment's specific clinical profile:
     *   - 'basc_standard' : BASC-3/4, BRIEF-2, Conners 3/4, ASRS, CDI-2, MMPI-2
     *   - 'srs2_severity' : SRS-2 (<=59 Normal, 60-65 Mild, 66-75 Moderate, >=76 Severe)
     *   - 'eddt_clinical' : EDDT (<=54 Normal, 55-59 Mild at Risk, 60-69 Moderate Clinical, 70-79 High Clinical, >=80 Very High Clinical)
     *   - 'masc2_anxiety' : MASC-2 (<40 Low, 40-54 Average, 55-59 High Average, 60-64 Slightly Elevated, 65-69 Elevated, >=70 Very Elevated)
     *   - 'spm2_sensory'  : SPM-2 (<=59 Typical, 60-69 Some Problems, >=70 Definite Dysfunction)
     *
     * @param mixed $score
     * @param string $scaleType 'clinical' or 'adaptive'
     * @param string $profile
     * @return array
     */
    public static function classifyTScoreByProfile(
        $score,
        string $scaleType = self::SCALE_CLINICAL,
        string $profile = 'basc_standard'
    ): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        // Allow calling classifyTScoreByProfile($score, 'srs2_severity') directly
        if ($scaleType !== self::SCALE_CLINICAL && $scaleType !== self::SCALE_ADAPTIVE) {
            $profile = $scaleType;
            $scaleType = self::SCALE_CLINICAL;
        }

        $numScore = (float)$score;
        $pct = self::tScoreToPercentile((int)$numScore);

        // Adaptive behavior (Inverse): lower scores are clinically significant across all T-score tests
        if ($scaleType === self::SCALE_ADAPTIVE) {
            if ($numScore <= 30) {
                return self::buildScoreResult($numScore, '**', 'Clinically Significant', 'clinically significant', $pct, '#e06c6c', true, false, 'clinically_significant');
            }
            if ($numScore <= 40) {
                return self::buildScoreResult($numScore, '*', 'At-Risk', 'at-risk', $pct, '#ffe699', true, false, 'at_risk');
            }
            if ($numScore >= 60) {
                return self::buildScoreResult($numScore, '', 'High / Strength', 'high', $pct, '#b6d7a8', false, true, 'strength');
            }
            return self::buildScoreResult($numScore, '', 'Average', 'average', $pct, '', false, false, 'average');
        }

        // Profile 1: SRS-2 Autism Severity T-Scores (`report-srs-2.php`)
        if ($profile === 'srs2_severity') {
            if ($numScore >= 76) {
                return self::buildScoreResult($numScore, '***', 'Severe', 'severe', $pct, '#e06c6c', true, false, 'severe');
            }
            if ($numScore >= 66) {
                return self::buildScoreResult($numScore, '**', 'Moderate', 'moderate', $pct, '#ea9999', true, false, 'moderate');
            }
            if ($numScore >= 60) {
                return self::buildScoreResult($numScore, '*', 'Mild', 'mild', $pct, '#ffe699', true, false, 'mild');
            }
            return self::buildScoreResult($numScore, '', 'Normal', 'normal', $pct, '', false, false, 'normal');
        }

        // Profile 2: EDDT Emotional Disturbance T-Scores (`report-eddt.php`)
        if ($profile === 'eddt_clinical') {
            if ($numScore >= 80) {
                return self::buildScoreResult($numScore, '**', 'Very High Clinical', 'very high clinical', $pct, '#cc0000', true, false, 'very_high_clinical');
            }
            if ($numScore >= 70) {
                return self::buildScoreResult($numScore, '*', 'High Clinical', 'high clinical', $pct, '#e06c6c', true, false, 'high_clinical');
            }
            if ($numScore >= 60) {
                return self::buildScoreResult($numScore, '*', 'Moderate Clinical', 'moderate clinical', $pct, '#f6b26b', true, false, 'moderate_clinical');
            }
            if ($numScore >= 55) {
                return self::buildScoreResult($numScore, '', 'Mild at Risk', 'mild at risk', $pct, '#ffe699', true, false, 'mild_at_risk');
            }
            return self::buildScoreResult($numScore, '', 'Normal', 'normal', $pct, '', false, false, 'normal');
        }

        // Profile 3: MASC-2 Anxiety T-Scores (`report-masc-2.php`)
        if ($profile === 'masc2_anxiety') {
            if ($numScore >= 70) {
                return self::buildScoreResult($numScore, '**', 'Very Elevated', 'very elevated', $pct, '#e06c6c', true, false, 'very_elevated');
            }
            if ($numScore >= 65) {
                return self::buildScoreResult($numScore, '**', 'Elevated', 'elevated', $pct, '#ea9999', true, false, 'elevated');
            }
            if ($numScore >= 60) {
                return self::buildScoreResult($numScore, '*', 'Slightly Elevated', 'slightly elevated', $pct, '#ffe699', true, false, 'slightly_elevated');
            }
            if ($numScore >= 55) {
                return self::buildScoreResult($numScore, '', 'High Average', 'high average', $pct, '', false, false, 'high_average');
            }
            if ($numScore >= 40) {
                return self::buildScoreResult($numScore, '', 'Average', 'average', $pct, '', false, false, 'average');
            }
            return self::buildScoreResult($numScore, '', 'Low', 'low', $pct, '', false, false, 'low');
        }

        // Profile 4: SPM-2 Sensory Processing T-Scores (`report-spm.php`)
        if ($profile === 'spm2_sensory') {
            if ($numScore >= 70) {
                return self::buildScoreResult($numScore, '**', 'Definite Dysfunction', 'definite dysfunction', $pct, '#e06c6c', true, false, 'definite_dysfunction');
            }
            if ($numScore >= 60) {
                return self::buildScoreResult($numScore, '*', 'Some Problems', 'some problems', $pct, '#ffe699', true, false, 'some_problems');
            }
            return self::buildScoreResult($numScore, '', 'Typical', 'typical', $pct, '', false, false, 'typical');
        }

        // Default Profile: BASC-3 / BASC-4 / BRIEF-2 / Conners / ASRS / CDI-2 / MMPI-2
        if ($numScore >= 70) {
            return self::buildScoreResult($numScore, '**', 'Clinically Significant', 'clinically significant', $pct, '#e06c6c', true, false, 'clinically_significant');
        }
        if ($numScore >= 60) {
            return self::buildScoreResult($numScore, '*', 'At-Risk', 'at-risk', $pct, '#ffe699', true, false, 'at_risk');
        }
        return self::buildScoreResult($numScore, '', 'Average', 'average', $pct, '', false, false, 'average');
    }

    /**
     * Classifies an Archetype 2 Standard Score (Mean = 100, SD = 15, Range = 40-160).
     * Automatically looks up empirical percentile and checks clinical intervention threshold (< 85).
     *
     * @param float|int|string|null $score
     * @param float $interventionThreshold Default 85.0 (1 SD below mean)
     * @return array
     */
    public static function classifyStandardScore($score, float $interventionThreshold = 85.0): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        $numScore = (int)round((float)$score);
        $percentile = self::getStandardScorePercentile($numScore);

        if ($numScore < 70) {
            $range = 'Extremely Low';
            $color = '#e06c6c';
            $stars = '**';
        } elseif ($numScore <= 79) {
            $range = 'Well Below Average';
            $color = '#ea9999';
            $stars = '*';
        } elseif ($numScore <= 89) {
            $range = 'Below Average';
            $color = '#ffe699';
            $stars = ($numScore < $interventionThreshold) ? '*' : '';
        } elseif ($numScore <= 109) {
            $range = 'Average';
            $color = '';
            $stars = '';
        } elseif ($numScore <= 119) {
            $range = 'Above Average';
            $color = '#d9ead3';
            $stars = '';
        } elseif ($numScore <= 129) {
            $range = 'Superior';
            $color = '#b6d7a8';
            $stars = '';
        } else {
            $range = 'Very Superior';
            $color = '#93c47d';
            $stars = '';
        }

        $isWeakness = ($numScore < $interventionThreshold);
        $isStrength = ($numScore >= 115);
        $flag = $isWeakness ? 'intervention_recommended' : ($isStrength ? 'strength' : 'average');

        return self::buildScoreResult($numScore, $stars, $range, strtolower($range), $percentile, $color, $isWeakness, $isStrength, $flag);
    }

    /**
     * Classifies an Archetype 2 Subtest Scaled Score (Mean = 10, SD = 3, Range = 1-19).
     * Flags subtest clinical weaknesses (typically <= 6) and strengths (>= 13).
     *
     * @param float|int|string|null $score
     * @param float $weaknessCutoff Default 6.0 (<= 6 is a clinical subtest weakness)
     * @return array
     */
    public static function classifyScaledScore($score, float $weaknessCutoff = 6.0): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        $numScore = (int)round((float)$score);
        $percentile = self::getScaledScorePercentile($numScore);

        if ($numScore <= 3) {
            $range = 'Extremely Low';
            $color = '#e06c6c';
            $stars = '**';
        } elseif ($numScore <= 5) {
            $range = 'Well Below Average';
            $color = '#ea9999';
            $stars = '*';
        } elseif ($numScore <= 7) {
            $range = 'Below Average';
            $color = '#ffe699';
            $stars = ($numScore <= $weaknessCutoff) ? '*' : '';
        } elseif ($numScore <= 12) {
            $range = 'Average';
            $color = '';
            $stars = '';
        } elseif ($numScore <= 14) {
            $range = 'Above Average';
            $color = '#d9ead3';
            $stars = '';
        } else {
            $range = 'Superior';
            $color = '#b6d7a8';
            $stars = '';
        }

        $isWeakness = ($numScore <= $weaknessCutoff);
        $isStrength = ($numScore >= 13);
        $flag = $isWeakness ? 'subtest_weakness' : ($isStrength ? 'subtest_strength' : 'average');

        return self::buildScoreResult($numScore, $stars, $range, strtolower($range), $percentile, $color, $isWeakness, $isStrength, $flag);
    }

    /**
     * Classifies a Vineland-3 (VABS-3) Subdomain V-Scale Score (Mean = 15, SD = 3, Range = 1-24).
     *   - <= 9    : Low (**)
     *   - 10 - 12 : Moderately Low (*)
     *   - 13 - 17 : Adequate / Average
     *   - 18 - 20 : Moderately High
     *   - >= 21   : High
     *
     * @param float|int|string|null $score
     * @return array
     */
    public static function classifyVScaleScore($score): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        $numScore = (int)round((float)$score);
        // Convert V-scale (M=15, SD=3) to equivalent Standard Score (M=100, SD=15) for percentile lookup
        $equivStandard = (int)round(100 + (($numScore - 15) * 5));
        $percentile = self::getStandardScorePercentile($equivStandard);

        if ($numScore <= 9) {
            return self::buildScoreResult($numScore, '**', 'Low', 'low', $percentile, '#e06c6c', true, false, 'subtest_weakness');
        }
        if ($numScore <= 12) {
            return self::buildScoreResult($numScore, '*', 'Moderately Low', 'moderately low', $percentile, '#ffe699', true, false, 'subtest_weakness');
        }
        if ($numScore <= 17) {
            return self::buildScoreResult($numScore, '', 'Adequate', 'adequate', $percentile, '', false, false, 'average');
        }
        if ($numScore <= 20) {
            return self::buildScoreResult($numScore, '', 'Moderately High', 'moderately high', $percentile, '#d9ead3', false, true, 'subtest_strength');
        }
        return self::buildScoreResult($numScore, '', 'High', 'high', $percentile, '#b6d7a8', false, true, 'subtest_strength');
    }

    /**
     * Classifies SAED-3 (Scales for Assessing Emotional Disturbance) Scaled Scores (Mean = 10, SD = 3),
     * where HIGHER scaled scores indicate emotional disturbance pathology:
     *   - <= 13   : Not Indicative of Emotional Disturbance
     *   - 14 - 16 : Indicative of Emotional Disturbance (*)
     *   - >= 17   : Highly Indicative of Emotional Disturbance (**)
     *
     * @param float|int|string|null $score
     * @return array
     */
    public static function classifyPathologyScaledScore($score): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        $numScore = (int)round((float)$score);
        $percentile = self::getScaledScorePercentile($numScore);

        if ($numScore >= 17) {
            return self::buildScoreResult($numScore, '**', 'Highly Indicative of Emotional Disturbance', 'highly indicative of emotional disturbance', $percentile, '#e06c6c', true, false, 'highly_indicative');
        }
        if ($numScore >= 14) {
            return self::buildScoreResult($numScore, '*', 'Indicative of Emotional Disturbance', 'indicative of emotional disturbance', $percentile, '#ffe699', true, false, 'indicative');
        }
        return self::buildScoreResult($numScore, '', 'Not Indicative of Emotional Disturbance', 'not indicative of emotional disturbance', $percentile, '', false, false, 'not_indicative');
    }

    /**
     * Classifies an Archetype 3 Clinical Diagnostic Cutoff Score (e.g., ADI-R, SCQ >= 15, ADOS-2, Vanderbilt-2).
     *
     * @param float|int|string|null $score
     * @param float $cutoff
     * @return array
     */
    public static function classifyClinicalCutoff($score, float $cutoff): array {
        if ($score === null || $score === '' || !is_numeric($score)) {
            return self::emptyResult();
        }

        $numScore = (float)$score;
        $meetsOrExceeds = ($numScore >= $cutoff);
        $comparisonWord = ($numScore > $cutoff) ? 'above' : (($numScore == $cutoff) ? 'at' : 'below');
        $indicativeText = $meetsOrExceeds ? 'indicative' : 'not indicative';

        return [
            'score'              => $numScore,
            'cutoff'             => $cutoff,
            'stars'              => $meetsOrExceeds ? '**' : '',
            'range'              => $meetsOrExceeds ? 'At or Above Cutoff' : 'Below Cutoff',
            'raw_range'          => $comparisonWord,
            'comparison_word'    => $comparisonWord,
            'indicative_text'    => $indicativeText,
            'percentile'         => 'N/A',
            'percentile_ordinal' => 'N/A',
            'color'              => $meetsOrExceeds ? '#e06c6c' : '',
            'is_elevated'        => $meetsOrExceeds,
            'is_strength'        => false,
            'clinical_flag'      => $meetsOrExceeds ? 'indicative' : 'not_indicative',
        ];
    }

    /**
     * Computes the Kennedy Axis V Global Assessment of Functioning (GAF) score
     * by averaging the 4 core subscales (Psychological Impairment, Social Skills, Violence, Occupation/School)
     * and mapping each domain into its 10-point clinical bracket (e.g., '90_100', '80_89', ..., '0_9').
     *
     * @param array $subscales ['psychimpair' => 65, 'social' => 70, 'violence' => 85, 'occupation' => 60, ...]
     * @return array
     */
    public static function computeKennedyGAF(array $subscales): array {
        $coreKeys = ['psychimpair', 'social', 'violence', 'occupation'];
        $coreSum = 0.0;
        $coreCount = 0;
        $brackets = [];

        foreach ($subscales as $key => $val) {
            if ($val === null || $val === '' || $val === 'NR' || !is_numeric($val)) {
                continue;
            }
            $num = max(0, min(100, (int)round((float)$val)));
            $brackets[$key] = [
                'score'   => $num,
                'bracket' => self::gafScoreToBracketKey($num),
            ];
            if (in_array($key, $coreKeys, true)) {
                $coreSum += $num;
                $coreCount++;
            }
        }

        $gafEquivalent = ($coreCount > 0) ? round($coreSum / 4.0, 1) : 0.0;

        return [
            'gaf_score'        => $gafEquivalent,
            'gaf_bracket'      => self::gafScoreToBracketKey((int)round($gafEquivalent)),
            'domains_evaluated'=> $brackets,
        ];
    }

    /**
     * Converts a Subtest Scaled Score (M=10, SD=3) into an equivalent Standard Score (M=100, SD=15)
     * as used in the XBASS Cross-Battery converter (`report-xbass.php`).
     */
    public static function convertScaledToStandard($scaledScore): int {
        $s = (float)$scaledScore;
        return (int)max(40, min(160, round(100 + (($s - 10.0) * 5.0))));
    }

    /**
     * Computes a psychometric Confidence Interval around a score.
     */
    public static function computeConfidenceInterval($score, int $level = 95, float $margin90 = 5.0, float $margin95 = 6.0): array {
        if (!is_numeric($score)) {
            return ['level' => $level, 'low' => null, 'high' => null, 'formatted' => 'N/A'];
        }
        $val = (float)$score;
        $margin = ($level === 90) ? $margin90 : $margin95;
        $low = (int)max(40, round($val - $margin));
        $high = (int)min(160, round($val + $margin));

        return [
            'level'     => $level,
            'low'       => $low,
            'high'      => $high,
            'formatted' => "{$low}-{$high}",
        ];
    }

    /**
     * Ranks preference categories for Archetype 5 inventories (e.g., Dunn-Rankin Reward Preference).
     */
    public static function rankPreferences(array $categoryScores): array {
        $clean = [];
        foreach ($categoryScores as $label => $val) {
            if ($val !== null && $val !== '' && is_numeric($val)) {
                $clean[$label] = (float)$val;
            }
        }

        arsort($clean);
        $results = [];
        $rank = 1;
        $total = count($clean);

        foreach ($clean as $label => $val) {
            $tier = ($rank === 1) ? 'Highest Preference' : (($rank === $total && $total > 1) ? 'Lowest Preference' : 'Moderate Preference');
            $results[] = [
                'category'     => $label,
                'score'        => $val,
                'rank'         => $rank,
                'rank_ordinal' => ReportGrammar::formatOrdinal($rank),
                'tier'         => $tier,
            ];
            $rank++;
        }

        return $results;
    }

    public static function getStandardScorePercentile(int $score): string {
        if ($score <= 40) return '<0.1';
        if ($score >= 160) return '>99.9';
        return self::STANDARD_SCORE_PERCENTILES[$score] ?? '50';
    }

    public static function getScaledScorePercentile(int $score): string {
        if ($score <= 1) return '0.1';
        if ($score >= 19) return '99.9';
        return self::SCALED_SCORE_PERCENTILES[$score] ?? '50';
    }

    public static function tScoreToPercentile(int $tScore): string {
        if ($tScore <= 20) return '<1';
        if ($tScore >= 80) return '>99';
        $equivStandard = (int)round(100 + (($tScore - 50) * 1.5));
        return self::getStandardScorePercentile($equivStandard);
    }

    private static function gafScoreToBracketKey(int $score): string {
        if ($score >= 91) return '90_100';
        if ($score >= 81) return '80_89';
        if ($score >= 71) return '70_79';
        if ($score >= 61) return '60_69';
        if ($score >= 51) return '50_59';
        if ($score >= 41) return '40_49';
        if ($score >= 31) return '30_39';
        if ($score >= 21) return '20_29';
        if ($score >= 10) return '10_19';
        return '0_9';
    }

    private static function buildScoreResult(
        float|int $score,
        string $stars,
        string $range,
        string $rawRange,
        string $percentile,
        string $color,
        bool $isElevated,
        bool $isStrength,
        string $flag
    ): array {
        return [
            'score'              => $score,
            'stars'              => $stars,
            'range'              => $range,
            'raw_range'          => $rawRange,
            'percentile'         => $percentile,
            'percentile_ordinal' => ReportGrammar::formatOrdinal($percentile),
            'color'              => $color,
            'is_elevated'        => $isElevated,
            'is_strength'        => $isStrength,
            'clinical_flag'      => $flag,
        ];
    }

    /**
     * Compute Bell Curve normal distribution metrics for a Standard Score (Mean = 100, SD = 15).
     */
    public static function computeBellCurveMetrics(float $standardScore): array {
        $clampedScore = max(40.0, min(160.0, $standardScore));
        $percentage   = round((($clampedScore - 40.0) / 120.0) * 100.0, 2);
        $offsetPx     = round($percentage / 10.0, 2);
        $lookupKey    = (int) round($clampedScore);
        $percentile   = self::STANDARD_SCORE_PERCENTILES[$lookupKey] ?? '50';

        return [
            'score'              => $standardScore,
            'clamped_score'      => $clampedScore,
            'percentage'         => $percentage,
            'offset_px'          => $offsetPx,
            'width_css'          => "calc({$percentage}% - {$offsetPx}px)",
            'percentile'         => $percentile,
            'percentile_ordinal' => ReportGrammar::formatOrdinal($percentile),
            'sd_offset'          => round(($standardScore - 100.0) / 15.0, 2),
        ];
    }

    public static function formatOrdinal(string $num): string {
        return ReportGrammar::formatOrdinal($num);
    }

    private static function emptyResult(): array {
        return [
            'score'              => null,
            'stars'              => '',
            'range'              => 'N/A',
            'raw_range'          => 'N/A',
            'percentile'         => 'N/A',
            'percentile_ordinal' => 'N/A',
            'color'              => '',
            'is_elevated'        => false,
            'is_strength'        => false,
            'clinical_flag'      => 'none',
        ];
    }
}
