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
}
