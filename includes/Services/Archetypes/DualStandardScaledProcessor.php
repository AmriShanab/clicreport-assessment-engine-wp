<?php

/**
 * DualStandardScaledProcessor (Archetype 2 Engine - 96 Templates)
 *
 * Powers Dual Standard Scores (Mean=100, SD=15), Subtest Scaled Scores (Mean=10, SD=3),
 * Vineland-3 V-Scales (Mean=15, SD=3), SAED-3 Pathology Scaled Scores, and XBASS converter.
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Services\Narrative\NarrativeInterpolator;
use ClicReport\AssessmentEngine\Services\Narrative\ReportGrammar;
use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;
use ClicReport\AssessmentEngine\Services\Visuals\BellCurveService;

defined('ABSPATH') || exit;

final class DualStandardScaledProcessor implements ArchetypeProcessorInterface
{

    public function process(
        array $templateRow,
        array $sections,
        array $scales,
        array $inputData,
        NarrativeRuleRepository $narrativeRepo
    ): array {
        $context = NarrativeInterpolator::buildExamineeContext(
            $inputData['first_name'] ?? ($inputData['examinee']['first_name'] ?? ''),
            $inputData['gender'] ?? ($inputData['examinee']['gender'] ?? 'male')
        );

        $includeCi = ! empty($inputData['include_confidence_intervals']) || ! empty($inputData['options']['include_confidence_intervals']);
        $ciLevel   = (int) ($inputData['ci_level'] ?? ($inputData['options']['ci_level'] ?? 95));

        $narrativeHtml = '';
        if (! empty($templateRow['intro_narrative'])) {
            $intro          = NarrativeInterpolator::render($templateRow['intro_narrative'], $context);
            $narrativeHtml .= "<p class='assessment-intro'>{$intro}</p>";
        }

        if (isset($inputData['subtest_score']) && is_numeric($inputData['subtest_score'])) {
            $subScore       = (float) $inputData['subtest_score'];
            $convertedSS    = AssessmentScorer::convertScaledToStandard($subScore);
            $scoredConv     = AssessmentScorer::classifyStandardScore($convertedSS);
            $narrativeHtml .= "<p class='xbass-conversion'><strong>XBASS Score Conversion:</strong> A subtest Scaled Score of {$subScore} converts to an equivalent Standard Score of {$convertedSS} ({$scoredConv['percentile_ordinal']} percentile, <strong>" . esc_html($scoredConv['range']) . '</strong> range).</p>';
        }

        $composites       = [];
        $subtestsByParent = [];

        foreach ($scales as $scale) {
            $isChildSubtest = in_array(
                $scale['scale_type'],
                [
                    AssessmentScorer::SCALE_SCALED,
                    AssessmentScorer::SCALE_V_SCALE,
                    'subtest_t',
                ],
                true
            ) && ! empty($scale['parent_scale_id']);

            if ($isChildSubtest) {
                $subtestsByParent[(int) $scale['parent_scale_id']][] = $scale;
            } else {
                $composites[] = $scale;
            }
        }

        $scoresEvaluated = [];
        $weakComposites  = [];

        $slug          = (string) ($templateRow['template_slug'] ?? '');
        $config        = ! empty($templateRow['config_json']) ? (json_decode((string) $templateRow['config_json'], true) ?: []) : [];
        $bellCurveSvc  = new BellCurveService();
        $hasBellCurve  = $bellCurveSvc->isSupported($slug, 'dual_standard_scaled', $config);

        if ('report-wisc-v' === $slug) {
            return $this->processWisc5Extended(
                $templateRow,
                $scales,
                $inputData,
                $context,
                $bellCurveSvc,
                $hasBellCurve,
                $config
            );
        }

        if ('report-celf-5' === $slug || 'report-celf-5-page' === $slug) {
            return $this->processCelf5Extended(
                $templateRow,
                $scales,
                $inputData,
                $context,
                $bellCurveSvc,
                $hasBellCurve,
                $config
            );
        }
        $bellCurveBars = [];

        foreach ($composites as $comp) {
            $compId    = (int) $comp['id'];
            $scaleKey  = (string) $comp['scale_key'];
            $scaleType = (string) $comp['scale_type'];
            $rawVal    = $this->extractScoreValue($inputData, $scaleKey);

            if (null === $rawVal || '' === $rawVal) {
                continue;
            }

            $threshold = ! empty($comp['cutoff_threshold']) ? (float) $comp['cutoff_threshold'] : 85.0;
            $scored    = $this->scoreByScaleType($rawVal, $scaleType, $threshold);
            $ci        = AssessmentScorer::computeConfidenceInterval($scored['score'], $ciLevel);

            if ($hasBellCurve && (AssessmentScorer::SCALE_STANDARD === $scaleType || empty($scaleType)) && null !== $scored['score']) {
                $metric = $bellCurveSvc->computeMetrics(
                    $scored['score'],
                    $scaleKey,
                    (string) ($comp['title'] ?? $scaleKey),
                    count($bellCurveBars)
                );
                $scored['bell_curve'] = $metric;
                $bellCurveBars[]      = $metric;
            }

            if ($scored['is_elevated']) {
                $weakComposites[] = $comp['title'];
            }

            $childSubtests     = $subtestsByParent[$compId] ?? [];
            $evaluatedSubtests = [];
            $weakSubtestTitles = [];
            $subtestSentences  = [];

            foreach ($childSubtests as $sub) {
                $subKey = (string) $sub['scale_key'];
                $subRaw = $this->extractScoreValue($inputData, $subKey);
                if (null === $subRaw || '' === $subRaw) {
                    continue;
                }

                $subCutoff                    = ! empty($sub['cutoff_threshold']) ? (float) $sub['cutoff_threshold'] : 6.0;
                $subScored                    = $this->scoreByScaleType($subRaw, (string) $sub['scale_type'], $subCutoff);
                $evaluatedSubtests[$subKey] = array_merge(['title' => $sub['title'], 'scale_type' => $sub['scale_type']], $subScored);

                if ($subScored['is_elevated']) {
                    $weakSubtestTitles[] = $sub['title'];
                }

                $metricLabel = match ($sub['scale_type']) {
                    AssessmentScorer::SCALE_V_SCALE => 'v-scale score',
                    'subtest_t'                     => 'T-score',
                    default                         => 'scaled score',
                };

                $subContext = array_merge(
                    $context,
                    [
                        'subtest_title'      => $sub['title'],
                        'score'              => $subScored['score'],
                        'percentile'         => $subScored['percentile'],
                        'percentile_ordinal' => $subScored['percentile_ordinal'],
                        'descriptor'         => $subScored['range'],
                        'raw_range'          => $subScored['raw_range'],
                        'is_weakness'        => $subScored['is_elevated'],
                    ]
                );

                $subTitleEsc = esc_html((string) $sub['title']);
                $subDescEsc  = wp_kses_post((string) $sub['description']);
                $nameEsc     = esc_html($context['examinee_name']);
                $possEsc     = esc_html($context['possessive']);
                $rangeEsc    = esc_html((string) $subScored['range']);

                $subLine = "On the <em>{$subTitleEsc}</em> subtest, which {$subDescEsc}, {$nameEsc} achieved a {$metricLabel} of {$subScored['score']} ({$subScored['percentile_ordinal']} percentile), placing {$possEsc} performance in the {$rangeEsc} range.";
                if ($subScored['is_elevated'] && ! empty($sub['elevated_template'])) {
                    $subLine .= ' ' . NarrativeInterpolator::render($sub['elevated_template'], $subContext);
                }
                $subtestSentences[] = $subLine;
            }

            $scoresEvaluated[$scaleKey] = array_merge(
                [
                    'title'               => $comp['title'],
                    'scale_type'          => $scaleType,
                    'confidence_interval' => $ci,
                    'subtests'            => $evaluatedSubtests,
                ],
                $scored
            );

            $article         = ReportGrammar::indefiniteArticle((string) $scored['range']);
            $compMetricLabel = match ($scaleType) {
                AssessmentScorer::SCALE_SCALED_PATHOLOGY,
                AssessmentScorer::SCALE_SCALED  => 'Scaled Score',
                AssessmentScorer::SCALE_V_SCALE => 'V-Scale Score',
                default                         => 'Standard Score',
            };

            $compContext = array_merge(
                $context,
                [
                    'scale_title'        => $comp['title'],
                    'score'              => $scored['score'],
                    'percentile'         => $scored['percentile'],
                    'percentile_ordinal' => $scored['percentile_ordinal'],
                    'descriptor'         => $scored['range'],
                    'raw_range'          => $scored['raw_range'],
                    'article'            => $article,
                    'ci_range'           => $ci['formatted'],
                    'ci_level'           => $ci['level'],
                    'include_ci'         => $includeCi,
                    'is_elevated'        => $scored['is_elevated'],
                ]
            );

            $safeKey     = esc_attr($scaleKey);
            $compTitle   = esc_html((string) $comp['title']);
            $nameEsc     = esc_html($context['examinee_name']);
            $compRange   = esc_html((string) $scored['range']);

            $para  = "<p class='{$safeKey}_p'><strong>{$compTitle}:</strong> ";
            $para .= NarrativeInterpolator::render($comp['description'], $compContext) . ' ';
            $para .= "{$nameEsc} earned a {$compMetricLabel} of {$scored['score']} ";
            if ($includeCi && AssessmentScorer::SCALE_STANDARD === $scaleType) {
                $para .= "({$ci['level']}% CI: {$ci['formatted']}) ";
            }
            $para .= "on the {$compTitle} scale, which ranks at the {$scored['percentile_ordinal']} percentile and falls within {$article} <strong>{$compRange}</strong> classification.";

            if ($scored['is_elevated'] && ! empty($comp['elevated_template'])) {
                $para .= ' ' . NarrativeInterpolator::render($comp['elevated_template'], $compContext);
            } elseif (! $scored['is_elevated'] && ! empty($comp['average_template'])) {
                $para .= ' ' . NarrativeInterpolator::render($comp['average_template'], $compContext);
            }

            if (! empty($subtestSentences)) {
                $para .= ' ' . implode(' ', $subtestSentences);
            }

            if (! empty($weakSubtestTitles)) {
                $weakList = esc_html(ReportGrammar::joinList($weakSubtestTitles, 'and'));
                $para    .= " Specifically, {$nameEsc} demonstrated relative subtest weakness in {$weakList}, warranting targeted instructional support.";
            }

            $para          .= '</p>';
            $narrativeHtml .= $para;
        }

        if (empty($scales)) {
            $dynamicScores = $inputData['scores'] ?? [];
            if (empty($dynamicScores)) {
                foreach ($inputData as $k => $v) {
                    if (is_numeric($v) && ! in_array($k, ['active_raters', 'user_id', 'nonce', 'timestamp', 'allow_partial'], true)) {
                        $cleanKey = preg_replace('/_[0-9]+$/', '', $k);
                        $dynamicScores[$cleanKey] = (float) $v;
                    }
                }
            }
            foreach ($dynamicScores as $key => $val) {
                $scored = AssessmentScorer::classifyStandardScore($val);
                if (null === $scored['score']) {
                    continue;
                }
                $title = ucwords(str_replace(['_', '-'], ' ', sanitize_text_field((string) $key)));
                if ($hasBellCurve && null !== $scored['score']) {
                    $metric = $bellCurveSvc->computeMetrics(
                        $scored['score'],
                        (string) $key,
                        $title,
                        count($bellCurveBars)
                    );
                    $scored['bell_curve'] = $metric;
                    $bellCurveBars[]      = $metric;
                }
                $scoresEvaluated[$key]   = array_merge(['title' => $title], $scored);
                if ($scored['is_elevated']) {
                    $weakComposites[] = $title;
                }
                $article        = ReportGrammar::indefiniteArticle((string) $scored['range']);
                $keyAttr        = esc_attr(sanitize_key((string) $key));
                $narrativeHtml .= "<p class='{$keyAttr}_p'><strong>" . esc_html($title) . ':</strong> ' . esc_html($context['examinee_name']) . " earned a Standard Score of {$scored['score']} ({$scored['percentile_ordinal']} percentile), placing " . esc_html($context['possessive']) . " performance in {$article} <strong>" . esc_html($scored['range']) . '</strong> range.</p>';
            }

            $dynamicSubtests = $inputData['subtests'] ?? [];
            foreach ($dynamicSubtests as $sKey => $sVal) {
                $sScored = AssessmentScorer::classifyScaledScore($sVal);
                if (null === $sScored['score']) {
                    continue;
                }
                $sTitle                              = ucwords(str_replace(['_', '-'], ' ', sanitize_text_field((string) $sKey)));
                $scoresEvaluated['subtests'][$sKey] = array_merge(['title' => $sTitle], $sScored);
                $sKeyAttr                            = esc_attr(sanitize_key((string) $sKey));
                $narrativeHtml                      .= "<p class='{$sKeyAttr}_sub_p'><em>" . esc_html($sTitle) . ':</em> ' . esc_html($context['examinee_name']) . " achieved a Scaled Score of {$sScored['score']} ({$sScored['percentile_ordinal']} percentile, <strong>" . esc_html($sScored['range']) . '</strong>).</p>';
            }
        }

        if (! empty($weakComposites)) {
            $weakList       = esc_html(ReportGrammar::joinList($weakComposites, 'and'));
            $narrativeHtml .= "<p class='clinical-summary'><strong>Clinical Summary:</strong> Overall results indicate that " . esc_html($context['examinee_name']) . " demonstrated clinically significant or below-threshold findings in {$weakList}. Targeted academic/clinical interventions and accommodations are recommended to address these areas.</p>";
        } elseif (! empty($scoresEvaluated)) {
            $narrativeHtml .= "<p class='clinical-summary'><strong>Clinical Summary:</strong> Overall results indicate that " . esc_html($context['possessive_name']) . ' composite scores fell within age-expected ranges across all evaluated domains.</p>';
        }

        $perfSummaryLines = [];
        foreach ($scoresEvaluated as $sKey => $sData) {
            if ('subtests' === $sKey || empty($sData['title']) || !isset($sData['score']) || null === $sData['score']) {
                continue;
            }
            // Skip subtests (scaled scores 1-19) so only standard score composites appear
            if (isset($sData['scale_type']) && in_array($sData['scale_type'], [AssessmentScorer::SCALE_SCALED, AssessmentScorer::SCALE_V_SCALE, 'subtest_t'], true)) {
                continue;
            }

            $titleEsc = esc_html((string) $sData['title']);
            $ssVal    = (int) $sData['score'];
            $prVal    = esc_html((string) ($sData['percentile'] ?? '50'));
            $rangeEsc = esc_html((string) ($sData['range'] ?? 'Average'));
            $perfSummaryLines[] = "{$titleEsc}: SS={$ssVal}; PR= {$prVal} &nbsp;&nbsp;{$rangeEsc}";
        }

        $performanceSummaryHtml = '';
        if (!empty($perfSummaryLines)) {
            $performanceSummaryHtml = "<p><u><b>Performance Summary</b></u></p><p>Performance Summary:<br><br>\n" . implode(" <br>\n", $perfSummaryLines) . " </p>";
        }

        $narrativeSummaryHtml = '';
        if (! empty($scoresEvaluated)) {
            $nameEsc  = esc_html($context['examinee_name']);
            $subjEsc  = esc_html($context['subject']);
            $fsiqKey  = isset($scoresEvaluated['fsiq']) ? 'fsiq' : (isset($scoresEvaluated['full_scale_iq']) ? 'full_scale_iq' : array_key_first($scoresEvaluated));
            $fsiqData = $scoresEvaluated[$fsiqKey] ?? null;
            $fsiqRange = $fsiqData ? esc_html((string) $fsiqData['range']) : 'Average';
            $indexPhrases = [];
            foreach ($scoresEvaluated as $sKey => $sData) {
                if ('subtests' === $sKey || $sKey === $fsiqKey || empty($sData['title']) || ! isset($sData['score']) || null === $sData['score']) {
                    continue;
                }
                $tTitle         = esc_html((string) $sData['title']);
                $tRange         = esc_html((string) ($sData['range'] ?? 'Average'));
                $indexPhrases[] = "within the {$tRange} range for measures of {$tTitle}";
            }
            $moreSpec = ! empty($indexPhrases) ? ' More specifically, ' . $subjEsc . ' obtained a score ' . ReportGrammar::joinList($indexPhrases, 'and') . '.' : '';
            $narrativeSummaryHtml = "<p><u><b>Narrative Summary</b></u></p><p>Based on cognitive assessment results, {$nameEsc}'s full scale IQ falls within the {$fsiqRange} range.{$moreSpec}</p>";
        }
        $result = [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $narrativeHtml,
            'narrative_summary_html' => $narrativeSummaryHtml,
            'performance_summary_html' => $performanceSummaryHtml
        ];

        if ($hasBellCurve && ! empty($bellCurveBars)) {
            $tplName              = (string) ($templateRow['template_name'] ?? 'Assessment');
            $result['bell_curve'] = [
                'enabled'  => true,
                'template' => $tplName,
                'bars'     => $bellCurveBars,
                'html'     => $bellCurveSvc->generateHtml($tplName, $bellCurveBars),
                'svg'      => $bellCurveSvc->generateSvg($tplName, $bellCurveBars),
            ];
        }

        return $result;
    }

    private function scoreByScaleType(mixed $rawVal, string $scaleType, float $threshold): array
    {
        return match ($scaleType) {
            AssessmentScorer::SCALE_SCALED           => AssessmentScorer::classifyScaledScore($rawVal, $threshold),
            AssessmentScorer::SCALE_V_SCALE          => AssessmentScorer::classifyVScaleScore($rawVal),
            AssessmentScorer::SCALE_SCALED_PATHOLOGY => AssessmentScorer::classifyPathologyScaledScore($rawVal),
            'subtest_t'                              => AssessmentScorer::classifyTScore($rawVal, AssessmentScorer::SCALE_ADAPTIVE),
            default                                  => AssessmentScorer::classifyStandardScore($rawVal, $threshold),
        };
    }

    private function extractScoreValue(array $inputData, string $key): mixed
    {
        $candidateKeys = [
            $key,
            "{$key}_1",
            "{$key}1",
            "{$key}_score",
            "{$key}_standard",
            "{$key}_scaled",
        ];
        $aliasMap      = [
            'fsiq'                 => ['full_scale_iq', 'full_scale', 'fsiq_composite'],
            'vci'                  => ['verbal_comprehension', 'verbal_comprehension_index'],
            'vsi'                  => ['visual_spatial', 'visual_spatial_index'],
            'fri'                  => ['fluid_reasoning', 'fluid_reasoning_index'],
            'wmi'                  => ['working_memory', 'working_memory_index'],
            'psi'                  => ['processing_speed', 'processing_speed_index'],
            'full_scale_iq'        => ['fsiq'],
            'verbal_comprehension' => ['vci'],
            'visual_spatial'       => ['vsi'],
            'fluid_reasoning'      => ['fri'],
            'working_memory'       => ['wmi'],
            'processing_speed'     => ['psi'],
            'core_language'        => ['core_language_score', 'core_language_score.'],
            'communication'        => ['communication_domain', 'communication_domain1', 'communication_domain_1'],
            'daily_living_skills'  => ['daily_living_skills_domain', 'daily_living_skills_domain1', 'daily_living_skills_domain_1'],
            'domestic'             => ['domesticnumeric', 'domesticnumeric1', 'domesticnumeric_1'],
            'socialization'        => ['socialization_domain', 'socialization_domain1', 'socialization_domain_1'],
            'play_and_leisure'     => ['play_and_leisure_time', 'play_and_leisure_time1', 'play_and_leisure_time_1'],
        ];
        if (isset($aliasMap[$key])) {
            foreach ($aliasMap[$key] as $alias) {
                $candidateKeys[] = $alias;
                $candidateKeys[] = "{$alias}_1";
                $candidateKeys[] = "{$alias}1";
            }
        }

        foreach ($candidateKeys as $k) {
            if (isset($inputData[$k]) && '' !== $inputData[$k]) {
                return $inputData[$k];
            }
            if (isset($inputData['scores'][$k]) && '' !== $inputData['scores'][$k]) {
                return $inputData['scores'][$k];
            }
            if (isset($inputData['subtests'][$k]) && '' !== $inputData['subtests'][$k]) {
                return $inputData['subtests'][$k];
            }
        }

        // Fuzzy search: any key ending with _{key} or {domain}_{key} (e.g. general_similarities, nonverbal_block_design)
        foreach ($candidateKeys as $ck) {
            $pattern = '/(^|_)' . preg_quote($ck, '/') . '$/i';
            foreach ($inputData as $k => $v) {
                if ('' === $v || null === $v || ! is_numeric($v)) {
                    continue;
                }
                if (preg_match($pattern, $k)) {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * Dedicated clinical processor for WISC-V generating the exact 6-paragraph CHC neuropsychological model.
     */
    private function processWisc5Extended(
        array $templateRow,
        array $scales,
        array $inputData,
        array $context,
        BellCurveService $bellCurveSvc,
        bool $hasBellCurve,
        array $config
    ): array {
        $name     = ! empty( $context['name'] ) ? esc_html( $context['name'] ) : 'The examinee';
        $possName = ! empty( $context['possessive_name'] ) ? esc_html( $context['possessive_name'] ) : "The examinee's";
        $his_her  = ! empty( $context['possessive'] ) ? esc_html( $context['possessive'] ) : 'his';
        $he_she   = ! empty( $context['pronoun'] ) ? esc_html( $context['pronoun'] ) : 'he';
        $him_her  = ! empty( $context['objective'] ) ? esc_html( $context['objective'] ) : 'him';

        // Extract scores for FSIQ and indices
        $fsiqVal = $this->extractScoreValue( $inputData, 'full_scale_iq' );
        if ( null === $fsiqVal ) {
            $fsiqVal = $this->extractScoreValue( $inputData, 'fsiq' );
        }
        $vciVal = $this->extractScoreValue( $inputData, 'verbal_comprehension' );
        if ( null === $vciVal ) {
            $vciVal = $this->extractScoreValue( $inputData, 'vci' );
        }
        $vsiVal = $this->extractScoreValue( $inputData, 'visual_spatial' );
        if ( null === $vsiVal ) {
            $vsiVal = $this->extractScoreValue( $inputData, 'vsi' );
        }
        $friVal = $this->extractScoreValue( $inputData, 'fluid_reasoning' );
        if ( null === $friVal ) {
            $friVal = $this->extractScoreValue( $inputData, 'fri' );
        }
        $wmiVal = $this->extractScoreValue( $inputData, 'working_memory' );
        if ( null === $wmiVal ) {
            $wmiVal = $this->extractScoreValue( $inputData, 'wmi' );
        }
        $psiVal = $this->extractScoreValue( $inputData, 'processing_speed' );
        if ( null === $psiVal ) {
            $psiVal = $this->extractScoreValue( $inputData, 'psi' );
        }

        // Subtests (Scaled Scores)
        $simVal = $this->extractScoreValue( $inputData, 'similarities' );
        $vocVal = $this->extractScoreValue( $inputData, 'vocabulary' );
        $bdVal  = $this->extractScoreValue( $inputData, 'block_design' );
        $vpVal  = $this->extractScoreValue( $inputData, 'visual_puzzles' );
        $mrVal  = $this->extractScoreValue( $inputData, 'matrix_reasoning' );
        $fwVal  = $this->extractScoreValue( $inputData, 'figure_weights' );
        $dsVal  = $this->extractScoreValue( $inputData, 'digit_span' );
        $psVal  = $this->extractScoreValue( $inputData, 'picture_span' );
        $cdVal  = $this->extractScoreValue( $inputData, 'coding' );
        $ssVal  = $this->extractScoreValue( $inputData, 'symbol_search' );

        $fsiqCl = AssessmentScorer::classifyStandardScore( $fsiqVal );
        $vciCl  = AssessmentScorer::classifyStandardScore( $vciVal );
        $vsiCl  = AssessmentScorer::classifyStandardScore( $vsiVal );
        $friCl  = AssessmentScorer::classifyStandardScore( $friVal );
        $wmiCl  = AssessmentScorer::classifyStandardScore( $wmiVal );
        $psiCl  = AssessmentScorer::classifyStandardScore( $psiVal );

        $simCl = AssessmentScorer::classifyScaledScore( $simVal );
        $vocCl = AssessmentScorer::classifyScaledScore( $vocVal );
        $bdCl  = AssessmentScorer::classifyScaledScore( $bdVal );
        $vpCl  = AssessmentScorer::classifyScaledScore( $vpVal );
        $mrCl  = AssessmentScorer::classifyScaledScore( $mrVal );
        $fwCl  = AssessmentScorer::classifyScaledScore( $fwVal );
        $dsCl  = AssessmentScorer::classifyScaledScore( $dsVal );
        $psCl  = AssessmentScorer::classifyScaledScore( $psVal );
        $cdCl  = AssessmentScorer::classifyScaledScore( $cdVal );
        $ssCl  = AssessmentScorer::classifyScaledScore( $ssVal );

        // Dynamic facilitation / impact phrase based on composite score
        $getImpactPhrase = function( $cl, $his_her ) {
            $score = (float) ( $cl['score'] ?? 100 );
            if ( $score >= 90 ) {
                return "which suggests that these skills facilitate {$his_her} learning and performance.";
            }
            if ( $score >= 80 ) {
                return "which suggests that these skills may occasionally pose vulnerabilities for {$his_her} learning and performance.";
            }
            return "which suggests that these difficulties significantly impact {$his_her} classroom learning and performance.";
        };

        $getSentImpact = function( $cl, $his_her ) {
            $score = (float) ( $cl['score'] ?? 100 );
            if ( $score >= 90 ) {
                return "His performance on the measures suggests that these skills facilitate {$his_her} learning and performance.";
            }
            if ( $score >= 80 ) {
                return "His performance suggests that these skills may occasionally present challenges in learning.";
            }
            return "His performance suggests that weaknesses in this area significantly hinder learning efficiency.";
        };

        // Paragraph 1: Overall Cognitive Processing Ability
        $p1 = "<p class=''>Overall Cognitive Processing Ability: The Wechsler Intelligence Scale for Children, Fifth Edition (WISC-V) was administered for the cognitive assessment. The WISC-V is a standardized measure of cognitive development for children between the ages of six and sixteen that places a strong emphasis on child-friendly, developmentally appropriate features and tasks. The WISC-V is an individually administered assessment that reports scores as Standard Scores (SS) with a mean (average) of 100 and standard deviation of 15, with most people scoring between 85 and 115. Cognitive, or mental, processes are foundational skills used in the act of thinking, remembering, and learning. These processes, along with other abilities and traits, combine to form an individual's general intellectual ability. Tests administered in the school environment do not capture every aspect of intelligence; however, these tests are useful in predicting how intense instruction needs to be in order for the individual to master academic content. {$possName} overall performance on the school-based measure of cognitive processing abilities falls within the {$fsiqCl['range']} range (SS= {$fsiqCl['score']}; PR= {$fsiqCl['percentile']}).</p>";

        // Paragraph 2: Verbal Comprehension (Gc)
        $vciImpact = $getImpactPhrase( $vciCl, $his_her );
        $p2 = "<p class=''>Verbal Comprehension (Gc): Verbal comprehension refers to an individual's knowledge base (or general fund of information) that has built up over time, beginning in infancy. It is like your own personal library of everything you know. Verbal Comprehension involves knowledge of one's culture (e.g., who is the President of the United States?) as well as verbal- or language-based knowledge that has been developed during general life experiences and formal schooling (e.g., understanding words and their meaning, understanding street signs, acquiring developmentally appropriate knowledge of current events and history). Having well developed Verbal Comprehension skills means that one understands and uses language well, has an average or better vocabulary, has good listening skills, and is able to use language well via verbal expression. For the Similarities subtest, two words were read to {$name} and {$he_she} was asked to describe how they were similar (SS= {$simCl['score']}). Additionally, on the Vocabulary subtest {$name} was asked to either name depicted items shown to {$him_her} or define words that were orally presented (SS= {$vocCl['score']}). {$possName} overall performance on the measures of Verbal Comprehension fell within the {$vciCl['range']} range (SS= {$vciCl['score']}; PR= {$vciCl['percentile']}), {$vciImpact}</p>";

        // Paragraph 3: Visual Processing (Gv)
        $vsiScore = (float) ( $vsiCl['score'] ?? 100 );
        $vsiSent = ( $vsiScore >= 90 ) ? "His performance on the Visual Processing tasks suggests that these skills facilitate {$his_her} learning and performance." : "His performance on the Visual Processing tasks suggests that difficulties in visual-spatial processing may impact classroom performance.";
        $p3 = "<p class=''>Visual Processing (Gv): Visual Processing, or visual spatial processing, refers to an individual's ability to think about visual patterns (e.g., what is the shortest route from your house to school?) and visual images (e.g., what would this shape look like if I turned it upside down?). This type of ability also involves generating, perceiving, and analyzing visual patterns and visual information (e.g., which three shapes go together to make this shape?). Additional examples of this type of ability include putting puzzles together, completing a maze (such as the ones often seen on children's menus in restaurants), and interpreting a graph or chart. For the Block Design subtest, {$name} was given blocks and shown a model and/or picture and asked to recreate the design shown (SS= {$bdCl['score']}). For the Visual Puzzles subtest, {$name} was asked to view a completed puzzle and select three response options that, when combined, reconstruct the puzzle (SS= {$vpCl['score']}). {$possName} overall performance on the measures of Visual Processing fell within the {$vsiCl['range']} range (SS= {$vsiCl['score']}; PR= {$vsiCl['percentile']}). {$vsiSent}</p>";

        // Paragraph 4: Fluid Reasoning (Gf)
        $friScore = (float) ( $friCl['score'] ?? 100 );
        $friSent = ( $friScore >= 90 ) ? "His performance on the Fluid Reasoning tasks suggests that these skills facilitate {$his_her} learning and performance." : "His performance on the Fluid Reasoning tasks suggests that novel reasoning difficulties may impact complex problem-solving in class.";
        $p4 = "<p class=''>Fluid Reasoning (Gf): Fluid Reasoning refers to the type of thinking that an individual may use when faced with a relatively new task that cannot be performed automatically (e.g., by relying on previously learned habits or scripts). It includes such tasks as forming and recognizing concepts (e.g., how are a dog, cat, and cow alike?), identifying and perceiving relationships (e.g., sun is to morning as moon is to night), drawing inferences (e.g., after reading a story, answering the question- What will John do next?), and/or reorganizing or transforming information (e.g., selecting one of several pictures to complete a puzzle/pattern). Overall, this ability can be thought of as a problem-solving type of intelligence. Fluid reasoning/problem solving skills are important for reading comprehension (e.g., making inferences from text), math (e.g., figuring out how to set up a math problem by using information provided in a word problem), and writing (e.g., writing a persuasive essay). For the Matrix Reasoning subtest, {$name} viewed an incomplete matrix or series and was asked to select the response option that completed the matrix or series (SS= {$mrCl['score']}). For the Figure Weights subtest, under the constraint of time, {$name} was shown a scale with missing weight(s) and asked to select the response option that would keep the scale balanced (SS= {$fwCl['score']}). {$possName} overall performance on the measures of Fluid Reasoning fell within the {$friCl['range']} range (SS= {$friCl['score']}; PR= {$friCl['percentile']}). {$friSent}</p>";

        // Paragraph 5: Working Memory (Gsm)
        $wmiScore = (float) ( $wmiCl['score'] ?? 100 );
        $wmiSent = ( $wmiScore >= 90 ) ? "His performance on the Working Memory tasks suggests that these skills facilitate {$his_her} learning and performance." : "His performance on the Working Memory tasks suggests that limitations in working memory may impact multi-step classroom instructions.";
        $p5 = "<p class=''>Working Memory (Gsm): Working memory is the ability to hold information in one's mind and then use it within a few seconds. The most common example of working memory is holding a phone number in one's mind long enough to dial it. Working memory is also a part of the short-term memory system and involves manipulating or transforming information and using it in some way (e.g., saying the months of the year backwards or doing mental math). Working Memory skills are important for reading, writing, and math. For the Digit Span subtest, {$name} was read a sequence of numbers and asked to recall the numbers in the same order, reverse order, and ascending order (SS= {$dsCl['score']}). For the Picture Span subtest, {$he_she} was shown a stimulus page with one or more pictures of nameable objects for a specified time and then asked to select the picture(s) from options on a response page (SS= {$psCl['score']}). {$possName} overall performance on the Working Memory measures fell within the {$wmiCl['range']} range (SS= {$wmiCl['score']}; PR= {$wmiCl['percentile']}). {$wmiSent}</p>";

        // Paragraph 6: Processing Speed (Gs)
        $psiScore = (float) ( $psiCl['score'] ?? 100 );
        $psiSent = ( $psiScore >= 90 ) ? "His performance on the Processing Speed tasks suggests that these skills facilitate {$his_her} learning and performance." : "His performance on the Processing Speed tasks suggests that slower processing speed may reduce efficiency on timed tasks.";
        $p6 = "<p class=''>Processing Speed (Gs): Processing Speed involves the ability to fluently perform simple clerical-type tasks, especially when under pressure to maintain focused attention and concentration. It can also be thought of as how quickly one can think or how quickly one can take simple tests that require simple decisions (e.g., math calculation fluency). Faster processing of information can help conserve working memory resources. On the other hand, impairment in this area reduces a person's efficiency in performing all types of mental operations causing them to respond more slowly to classroom tasks. For the Coding subtest, {$name} worked within a specified time limit and used a key to copy symbols that corresponded with simple geometric shapes or numbers (SS= {$cdCl['score']}). For the Symbol Search subtest, {$he_she} scanned search groups and indicated whether target symbols were present (SS= {$ssCl['score']}). {$possName} overall performance on the measures of Processing Speed fell within the {$psiCl['range']} range (SS= {$psiCl['score']}; PR= {$psiCl['percentile']}). {$psiSent}</p>";

        $narrativeHtml = $p1 . $p2 . $p3 . $p4 . $p5 . $p6;

        // Narrative Summary
        $narrativeSummaryHtml = "<p><u><b>Narrative Summary</b></u></p><p>Based on cognitive assessment results, {$possName} full scale IQ falls within the {$fsiqCl['range']} range. More specifically, {$he_she} obtained a score within the {$vciCl['range']} range for measures of Verbal Comprehension, within the {$vsiCl['range']} range for measures of Visual Spatial abilities, within the {$friCl['range']} range on measures of Fluid Reasoning, within the {$wmiCl['range']} range for measures of Working Memory, and within the {$psiCl['range']} range on measures of Processing Speed.</p>";

        // Performance Summary
        $performanceSummaryHtml = "<p><u><b>Performance Summary</b></u></p><p>Performance Summary:<br><br>\nFull Scale IQ: SS={$fsiqCl['score']}; PR= {$fsiqCl['percentile']} &nbsp;&nbsp;{$fsiqCl['range']} <br>\nVerbal Comprehension: SS={$vciCl['score']}; PR= {$vciCl['percentile']} &nbsp;&nbsp;{$vciCl['range']} <br>\nVisual Spatial: SS={$vsiCl['score']}; PR= {$vsiCl['percentile']} &nbsp;&nbsp;{$vsiCl['range']} <br>\nFluid Reasoning: SS={$friCl['score']}; PR= {$friCl['percentile']} &nbsp;&nbsp;{$friCl['range']} <br>\nWorking Memory: SS={$wmiCl['score']}; PR= {$wmiCl['percentile']} &nbsp;&nbsp;{$wmiCl['range']} <br>\nProcessing Speed: SS={$psiCl['score']}; PR= {$psiCl['percentile']} &nbsp;&nbsp;{$psiCl['range']}</p>";

        // Build scoresEvaluated map for UI
        $scoresEvaluated = [
            'fsiq'                 => $fsiqCl,
            'full_scale_iq'        => $fsiqCl,
            'vci'                  => $vciCl,
            'verbal_comprehension' => $vciCl,
            'similarities'         => $simCl,
            'vocabulary'           => $vocCl,
            'vsi'                  => $vsiCl,
            'visual_spatial'       => $vsiCl,
            'block_design'         => $bdCl,
            'visual_puzzles'       => $vpCl,
            'fri'                  => $friCl,
            'fluid_reasoning'      => $friCl,
            'matrix_reasoning'     => $mrCl,
            'figure_weights'       => $fwCl,
            'wmi'                  => $wmiCl,
            'working_memory'       => $wmiCl,
            'digit_span'           => $dsCl,
            'picture_span'         => $psCl,
            'psi'                  => $psiCl,
            'processing_speed'     => $psiCl,
            'coding'               => $cdCl,
            'symbol_search'        => $ssCl,
        ];

        $result = [
            'examinee'                 => $context['examinee_name'],
            'pronouns'                 => $context,
            'scores_evaluated'         => $scoresEvaluated,
            'narrative_html'           => $narrativeHtml,
            'narrative_summary_html'   => $narrativeSummaryHtml,
            'performance_summary_html' => $performanceSummaryHtml,
        ];

        if ( $hasBellCurve ) {
            $tplName = (string) ( $templateRow['template_name'] ?? 'WISC-V' );
            $bars = [];
            $bellCurveScales = [
                'full_scale_iq'        => 'Full Scale IQ',
                'processing_speed'     => 'Processing Speed',
                'working_memory'       => 'Working Memory',
                'fluid_reasoning'      => 'Fluid Reasoning',
                'visual_spatial'       => 'Visual Spatial',
                'verbal_comprehension' => 'Verbal Comprehension',
            ];
            foreach ( $bellCurveScales as $bKey => $bLabel ) {
                $val = $scoresEvaluated[ $bKey ]['score'] ?? null;
                if ( null !== $val && is_numeric( $val ) ) {
                    $metrics = $bellCurveSvc->computeMetrics( (float) $val, 'standard_score' );
                    $bars[]  = array_merge( [ 'key' => $bKey, 'label' => $bLabel ], $metrics );
                }
            }
            if ( ! empty( $bars ) ) {
                $result['bell_curve']      = true;
                $result['bell_curve_html'] = $bellCurveSvc->generateHtml( $tplName, $bars );
            }
        }

        return $result;
    }

    /**
     * Process CELF-5 Extended Clinical Narrative
     */
    private function processCelf5Extended(
        array $templateRow,
        array $scales,
        array $inputData,
        array $context,
        BellCurveService $bellCurveSvc,
        bool $hasBellCurve,
        array $config
    ): array {
        $name     = ! empty( $context['name'] ) ? esc_html( $context['name'] ) : 'The examinee';
        $possName = ! empty( $context['possessive_name'] ) ? esc_html( $context['possessive_name'] ) : "The examinee's";
        $his_her  = ! empty( $context['possessive'] ) ? esc_html( $context['possessive'] ) : 'his';
        $he_she   = ! empty( $context['pronoun'] ) ? esc_html( $context['pronoun'] ) : 'he';
        $him_her  = ! empty( $context['objective'] ) ? esc_html( $context['objective'] ) : 'him';

        // Checkbox exclusions (if checked in the frontend, the section is deleted/suppressed)
        $excludeCoreLanguage     = !empty($inputData['core_language_score_index']) || !empty($inputData['exclude_core_language']);
        $excludeExpressive       = !empty($inputData['show_expressive_language_index']) || !empty($inputData['exclude_expressive']);
        $excludeLanguageStructure = !empty($inputData['show_language_structure_index']) || !empty($inputData['exclude_language_structure']);
        $excludeReceptive        = !empty($inputData['show_receptive_language_index']) || !empty($inputData['exclude_receptive']);
        $excludeLanguageContent  = !empty($inputData['show_language_content_index']) || !empty($inputData['exclude_language_content']);

        // Indices
        $coreScoreVal = $this->extractScoreValue($inputData, 'core_language_score');
        $repScoreVal  = $this->extractScoreValue($inputData, 'receptive_language_index');
        $expScoreVal  = $this->extractScoreValue($inputData, 'expressive_language_index');
        $cntScoreVal  = $this->extractScoreValue($inputData, 'language_content_index');
        $strScoreVal  = $this->extractScoreValue($inputData, 'language_structure_index');

        // Subtests (Receptive & Content)
        $scVal  = $this->extractScoreValue($inputData, 'sentence_comprehension');
        $wcVal  = $this->extractScoreValue($inputData, 'word_classes');
        $fdVal  = $this->extractScoreValue($inputData, 'following_directions');
        $lcVal  = $this->extractScoreValue($inputData, 'linguistic_concepts');
        $wcaVal = $this->extractScoreValue($inputData, 'word_classes_a') ?? $wcVal;
        $fdaVal = $this->extractScoreValue($inputData, 'following_directions_a') ?? $fdVal;

        // Subtests (Expressive & Structure)
        $wsVal  = $this->extractScoreValue($inputData, 'word_structure');
        $fsVal  = $this->extractScoreValue($inputData, 'formulated_sentences');
        $rsVal  = $this->extractScoreValue($inputData, 'recalling_sentences');
        $scaVal = $this->extractScoreValue($inputData, 'sentence_comprehension_a') ?? $scVal;
        $wsbVal = $this->extractScoreValue($inputData, 'word_structure_b') ?? $wsVal;
        $fsbVal = $this->extractScoreValue($inputData, 'formulated_sentences_b') ?? $fsVal;
        $rsbVal = $this->extractScoreValue($inputData, 'recalling_sentences_b') ?? $rsVal;

        // Classify
        $coreCl = AssessmentScorer::classifyStandardScore($coreScoreVal);
        $repCl  = AssessmentScorer::classifyStandardScore($repScoreVal);
        $expCl  = AssessmentScorer::classifyStandardScore($expScoreVal);
        $cntCl  = AssessmentScorer::classifyStandardScore($cntScoreVal);
        $strCl  = AssessmentScorer::classifyStandardScore($strScoreVal);

        $scCl  = AssessmentScorer::classifyScaledScore($scVal);
        $wcCl  = AssessmentScorer::classifyScaledScore($wcVal);
        $fdCl  = AssessmentScorer::classifyScaledScore($fdVal);
        $lcCl  = AssessmentScorer::classifyScaledScore($lcVal);
        $wcaCl = AssessmentScorer::classifyScaledScore($wcaVal);
        $fdaCl = AssessmentScorer::classifyScaledScore($fdaVal);

        $wsCl  = AssessmentScorer::classifyScaledScore($wsVal);
        $fsCl  = AssessmentScorer::classifyScaledScore($fsVal);
        $rsCl  = AssessmentScorer::classifyScaledScore($rsVal);
        $scaCl = AssessmentScorer::classifyScaledScore($scaVal);
        $wsbCl = AssessmentScorer::classifyScaledScore($wsbVal);
        $fsbCl = AssessmentScorer::classifyScaledScore($fsbVal);
        $rsbCl = AssessmentScorer::classifyScaledScore($rsbVal);

        // Helper for subtest clinical phrasing
        $subtestAbilityPhrase = function($scoreVal, $him_her) {
            $val = (float) ($scoreVal ?? 10);
            return ($val > 6) ? "age-appropriate abilities." : "a weakness for {$him_her}.";
        };

        $paragraphs = [];

        // Intro & Core Language
        $coreScoreNum = (float) ($coreCl['score'] ?? 100);
        if ($coreScoreNum < 85 && $coreScoreNum > 80) {
            $croText = "While {$possName} score is {$coreCl['range']}, this score is not considered to be concerning at this time. " . ucfirst($his_her) . " performance across subtests and indices are explained in further detail below:";
        } elseif ($coreScoreNum < 85) {
            $croText = "{$possName} score indicates that {$he_she} may be struggling with a language disorder. " . ucfirst($his_her) . " performance across subtests and indices are explained in further detail below:";
        } else {
            $croText = ucfirst($his_her) . " scores across subtests and indices are explained in further detail below:";
        }

        $coreLanguagePart = "";
        if (!$excludeCoreLanguage) {
            $coreLanguagePart = " <span class=\"core_language_score-row\">{$name} received a Core Language score within the {$coreCl['range']} range (SS= {$coreCl['score']};PR= {$coreCl['percentile']}). The Core Language score is a measure of general language ability, it is comprised of the Receptive Language Index and Expressive Language Index. <span class=\"cro\">{$croText}</span></span>";
        }

        $introP = "<p class=''>To better understand {$possName} language and communication skills {$he_she} was administered The Clinical Evaluation of Language Fundamentals, Fifth Edition (CELF-5). The CELF-5 is a language assessment that was designed to assess a student's language and communication skills in a variety of contexts, determine the presence of a language disorder, describe the nature of the language disorder, and can be used to plan for intervention or treatment based on the identified needs. The scaled score for each test of the CELF-5 is based on a mean of 10 with an average range of 7 to 13. The standard score for the Core Language Score and Index Scores are based on a mean of 100 with a standard deviation of 15 and an average range of 85 to 115.{$coreLanguagePart}</p>";
        $paragraphs[] = $introP;

        // Receptive Language Index
        if (!$excludeReceptive) {
            $repScoreNum = (float) ($repCl['score'] ?? 100);
            $reLText = ($repScoreNum <= 84) ? " {$name}'s performance on this index suggests that {$he_she} may be struggling to understand words and language at a rate commensurate with {$his_her} peers." : "";
            $pRep = "<p class='receptive_language_index-row'>Receptive Language Index: This is a measure of {$possName} listening and auditory comprehension skills. " . ucfirst($he_she) . " received a Receptive Language Index score within the {$repCl['range']} range (SS= {$repCl['score']};PR= {$repCl['percentile']}).{$reLText} This index score is comprised of the Sentence Comprehension, Word Classes, and Following Directions subtests.</p>";
            $paragraphs[] = $pRep;

            $pSc = "<p class='receptive_language_index-row v'><span class=\"indent_p\"></span>Sentence Comprehension: This subtest was used to evaluate {$possName} ability to interpret spoken sentences of increasing length and complexity and select the pictures that illustrated referential meaning of the sentence. " . ucfirst($he_she) . " received a score within the {$scCl['range']} range, indicating " . $subtestAbilityPhrase($scVal, $him_her) . " In a classroom environment, sentence comprehension and the understanding of relationships among spoken language, real-life references, and situations are emphasized when listening to stories or descriptions of events, as well as when matching sentences that are spoken or read to pictured references.</p>";
            $paragraphs[] = $pSc;

            $pWc = "<p class='receptive_language_index-row v'><span class=\"indent_p\"></span>Word Classes: This subtest was used to measure {$possName} ability to understand relationships between words based on semantic class features, function, or place/time of occurrence. " . ucfirst($he_she) . " received a score within the {$wcCl['range']} range, indicating " . $subtestAbilityPhrase($wcVal, $him_her) . " In a classroom environment, the knowledge and precise use of words for expressing meanings in written text is emphasized by comparing and contrasting related words for shared and non-shared meaning features, classifying words by semantic classes to form concept categories and semantic networks, and using antonyms and synonyms.</p>";
            $paragraphs[] = $pWc;

            $pFd = "<p class='receptive_language_index-row v'><span class=\"indent_p\"></span>Following Directions: This subtest was used to evaluate {$possName} ability to interpret spoken directions of increasing length and complexity by being asked to point to different shapes in a certain order among an array of shapes. This subtest also measures short-term and procedural memory abilities. {$name} received a score within the {$fdCl['range']} range, indicating " . $subtestAbilityPhrase($fdVal, $him_her) . " In a classroom environment comprehension, recall, and the ability to act upon spoken directions are essential for achieving in all subject areas and for internalizing scripts and rules for behavior. Remembering spoken directions supports the student’s ability to internalize scripts and rules for behavior, and for completing assignments or projects in school and at home.</p>";
            $paragraphs[] = $pFd;
        }

        // Expressive Language Index
        if (!$excludeExpressive) {
            $pExp = "<p class='expressive_language_index-row'>Expressive Language Index: This index measured how well {$name} can communicate {$his_her} wants and needs as well as {$his_her} ability to use intentionality, vocabulary, semantics (word/sentence meaning), morphology, and syntax (grammar rules). {$name} received an Expressive Language Index score within the {$expCl['range']} range (SS= {$expCl['score']};PR= {$expCl['percentile']}). This index score is comprised of the Word Structure, Formulated Sentences, and Recalling Sentences subtests.</p>";
            $paragraphs[] = $pExp;

            $pWs = "<p class='expressive_language_index-row'><span class=\"indent_p\"></span>Word Structure: This subtest measured {$possName} ability to apply word structure rules to mark inflection, derivations, and comparisons, as well as {$his_her} ability to select and use appropriate pronouns to refer to people, objects, and possessive relationships. {$name} received a score within the {$wsCl['range']} range, indicating " . $subtestAbilityPhrase($wsVal, $him_her) . " In a classroom environment, the use of word structure rules is emphasized by matching word forms to pictures; substituting pronouns for nouns; indicating number, time, and possessive relationships; making comparisons of characteristics; describing pictures and events; and other tasks.</p>";
            $paragraphs[] = $pWs;

            $pFs = "<p class='expressive_language_index-row'><span class=\"indent_p\"></span>Formulated Sentences: This subtest was used to evaluate {$possName} ability to formulate complete, semantically, and grammatically correct, spoken sentences of increasing length and complexity using given words and contextual constraints imposed by illustrations. This is also a measure or working memory. " . ucfirst($he_she) . " received a score within the {$fsCl['range']} range, indicating " . $subtestAbilityPhrase($fsVal, $him_her) . " In a classroom environment, the ability to formulate complete semantically-, syntactically-, and pragmatically-acceptable spoken and written sentences of increasing complexity is emphasized in (a) storytelling, (b) sentence completion, combination, and transformation activities, (c) written text, and (d) editing text and other literacy activities.</p>";
            $paragraphs[] = $pFs;

            $pRs = "<p class='expressive_language_index-row'><span class=\"indent_p\"></span>Recalling Sentences: This subtest measured {$possName} ability to listen to spoken sentences of increasing length and complexity and repeat the sentences without changing word meaning and content, word structure (morphology), or sentence structure (syntax). " . ucfirst($his_her) . " score on this subtest fell within the {$rsCl['range']} range, indicating " . $subtestAbilityPhrase($rsVal, $him_her) . " In a classroom environment, the ability to remember spoken sentences of increasing complexity in meaning and structure is required for following directions and academic instructions, writing to dictation, copying and note taking, learning vocabulary and related words, and subject content.</p>";
            $paragraphs[] = $pRs;
        }

        // Language Content Index
        if (!$excludeLanguageContent) {
            $pCnt = "<p class='language_content_index-row'>Language Content Index: This index measures various aspects of semantic development, including vocabulary, concept and category development, comprehension of associations and relationships among words, interpretation of factual and inferential information presented orally, and the ability to create meaningfully semantically- and syntactically- correct sentences. {$name} received a Language Content Index score within the {$cntCl['range']} range (SS= {$cntCl['score']};PR= {$cntCl['percentile']}). This index score is comprised of the Linguistic Concepts, Word Classes, and the Following Directions subtests.</p>";
            $paragraphs[] = $pCnt;

            $pLc = "<p class='language_content_index-row'><span class=\"indent_p\"></span>Linguistic Concepts: This subtest was used to measure {$possName} ability to interpret spoken directions with basic concepts, which requires logical operations such as inclusion and exclusion, orientation and timing, and identifying mentioned objects from several pictured choices. " . ucfirst($he_she) . " received a score within the {$lcCl['range']} range, indicating " . $subtestAbilityPhrase($lcVal, $him_her) . " In a classroom environment, well developed linguistic concept skills aid in the understanding of basic concepts such as and, before, or after is essential for following directions for hands-on activities, lessons, projects, and other assignments.</p>";
            $paragraphs[] = $pLc;

            if ($excludeReceptive) {
                // If receptive was excluded, render Word Classes and Following Directions under Content
                $pWca = "<p class='language_content_index-row'><span class=\"indent_p\"></span>Word Classes: This subtest was used to measure {$possName} ability to understand relationships between words based on semantic class features, function, or place/time of occurrence. " . ucfirst($he_she) . " received a score within the {$wcaCl['range']} range, indicating " . $subtestAbilityPhrase($wcaVal, $him_her) . " In a classroom environment, the knowledge and precise use of words for expressing meanings in written text is emphasized by comparing and contrasting related words for shared and non-shared meaning features, classifying words by semantic classes to form concept categories and semantic networks, and using antonyms and synonyms.</p>";
                $paragraphs[] = $pWca;

                $pFda = "<p class='language_content_index-row'><span class=\"indent_p\"></span>Following Directions: This subtest was used to evaluate {$possName} ability to interpret spoken directions of increasing length and complexity by being asked to point to different shapes in a certain order among an array of shapes. This subtest also measures short-term and procedural memory abilities. {$name} received a score within the {$fdaCl['range']} range, indicating " . $subtestAbilityPhrase($fdaVal, $him_her) . " In a classroom environment comprehension, recall, and the ability to act upon spoken directions are essential for achieving in all subject areas and for internalizing scripts and rules for behavior. Remembering spoken directions supports the student’s ability to internalize scripts and rules for behavior, and for completing assignments or projects in school and at home.</p>";
                $paragraphs[] = $pFda;
            }
        }

        // Language Structure Index
        if (!$excludeLanguageStructure) {
            $pStr = "<p class='language_structure_index-row'>Language Structure Index: The Language Structure index is an overall measure of receptive and expressive components of interpreting and producing sentence structure. {$name} received an overall Language Structure score within the {$strCl['range']} range (SS= {$strCl['score']};PR= {$strCl['percentile']}). The Language Structure index is comprised of the Sentence Comprehension, Word Structure, Formulated Sentences, and Recalling Sentences subtests.</p>";
            $paragraphs[] = $pStr;

            if ($excludeReceptive || $excludeExpressive) {
                // If either receptive or expressive was excluded, render subtests under Language Structure
                if ($excludeReceptive) {
                    $pSca = "<p class='language_structure_index-row'><span class=\"indent_p\"></span>Sentence Comprehension: This subtest was used to evaluate {$possName} ability to interpret spoken sentences of increasing length and complexity and select the pictures that illustrated referential meaning of the sentence. " . ucfirst($he_she) . " received a score within the {$scaCl['range']} range, indicating " . $subtestAbilityPhrase($scaVal, $him_her) . " In a classroom environment, sentence comprehension and the understanding of relationships among spoken language, real-life references, and situations are emphasized when listening to stories or descriptions of events, as well as when matching sentences that are spoken or read to pictured references.</p>";
                    $paragraphs[] = $pSca;
                }
                if ($excludeExpressive) {
                    $pWsb = "<p class='language_structure_index-row'><span class=\"indent_p\"></span>Word Structure: This subtest measured {$possName} ability to apply word structure rules to mark inflection, derivations, and comparisons, as well as {$his_her} ability to select and use appropriate pronouns to refer to people, objects, and possessive relationships. {$name} received a score within the {$wsbCl['range']} range, indicating " . $subtestAbilityPhrase($wsbVal, $him_her) . " In a classroom environment, the use of word structure rules is emphasized by matching word forms to pictures; substituting pronouns for nouns; indicating number, time, and possessive relationships; making comparisons of characteristics; describing pictures and events; and other tasks.</p>";
                    $paragraphs[] = $pWsb;

                    $pFsb = "<p class='language_structure_index-row'><span class=\"indent_p\"></span>Formulated Sentences: This subtest was used to evaluate {$possName} ability to formulate complete, semantically, and grammatically correct, spoken sentences of increasing length and complexity using given words and contextual constraints imposed by illustrations. This is also a measure or working memory. " . ucfirst($he_she) . " received a score within the {$fsbCl['range']} range, indicating " . $subtestAbilityPhrase($fsbVal, $him_her) . " In a classroom environment, the ability to formulate complete semantically-, syntactically-, and pragmatically-acceptable spoken and written sentences of increasing complexity is emphasized in (a) storytelling, (b) sentence completion, combination, and transformation activities, (c) written text, and (d) editing text and other literacy activities.</p>";
                    $paragraphs[] = $pFsb;

                    $pRsb = "<p class='language_structure_index-row'><span class=\"indent_p\"></span>Recalling Sentences: This subtest measured {$possName} ability to listen to spoken sentences of increasing length and complexity and repeat the sentences without changing word meaning and content, word structure (morphology), or sentence structure (syntax). " . ucfirst($his_her) . " score on this subtest fell within the {$rsbCl['range']} range, indicating " . $subtestAbilityPhrase($rsbVal, $him_her) . " In a classroom environment, the ability to remember spoken sentences of increasing complexity in meaning and structure is required for following directions and academic instructions, writing to dictation, copying and note taking, learning vocabulary and related words, and subject content.</p>";
                    $paragraphs[] = $pRsb;
                }
            }
        }

        $narrativeHtml = implode("\n\n", $paragraphs);

        // Build scoresEvaluated map
        $scoresEvaluated = [
            'core_language_score'       => $coreCl,
            'receptive_language_index'  => $repCl,
            'sentence_comprehension'    => $scCl,
            'word_classes'              => $wcCl,
            'following_directions'      => $fdCl,
            'expressive_language_index' => $expCl,
            'word_structure'            => $wsCl,
            'formulated_sentences'      => $fsCl,
            'recalling_sentences'       => $rsCl,
            'language_content_index'    => $cntCl,
            'linguistic_concepts'       => $lcCl,
            'word_classes_a'            => $wcaCl,
            'following_directions_a'    => $fdaCl,
            'language_structure_index'  => $strCl,
            'sentence_comprehension_a'  => $scaCl,
            'word_structure_b'          => $wsbCl,
            'formulated_sentences_b'    => $fsbCl,
            'recalling_sentences_b'     => $rsbCl,
        ];

        $result = [
            'examinee'         => $context['examinee_name'],
            'pronouns'         => $context,
            'scores_evaluated' => $scoresEvaluated,
            'narrative_html'   => $narrativeHtml,
        ];

        if ( $hasBellCurve ) {
            $tplName = (string) ( $templateRow['template_name'] ?? 'CELF-5' );
            $bars = [];
            $bellCurveScales = [
                'core_language_score'       => 'Core Language Score',
                'receptive_language_index'  => 'Receptive Language Index',
                'expressive_language_index' => 'Expressive Language Index',
                'language_content_index'    => 'Language Content Index',
                'language_structure_index'  => 'Language Structure Index',
            ];
            foreach ( $bellCurveScales as $bKey => $bLabel ) {
                $val = $scoresEvaluated[ $bKey ]['score'] ?? null;
                if ( null !== $val && is_numeric( $val ) ) {
                    $metrics = $bellCurveSvc->computeMetrics( (float) $val, 'standard_score' );
                    $bars[]  = array_merge( [ 'key' => $bKey, 'label' => $bLabel ], $metrics );
                }
            }
            if ( ! empty( $bars ) ) {
                $result['bell_curve']      = true;
                $result['bell_curve_html'] = $bellCurveSvc->generateHtml( $tplName, $bars );
            }
        }

        return $result;
    }
}
