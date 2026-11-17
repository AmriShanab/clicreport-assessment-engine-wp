<?php
/**
 * BellCurveService
 *
 * Psychometric Normal Distribution & Bell Curve Visualization Engine.
 * Supports Standard Score based assessments (Mean = 100, SD = 15, Range: 40 - 160).
 *
 * @package ClicReport\AssessmentEngine\Services\Visuals
 */

namespace ClicReport\AssessmentEngine\Services\Visuals;

use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

defined( 'ABSPATH' ) || exit;

final class BellCurveService {

    /**
     * Standard color palette matching legacy ClicReport template alternating bars.
     */
    public const LEGACY_BAR_COLORS = [
        0 => '#d9ead5', // Light green
        1 => '#cfcfcf', // Light gray
        2 => '#fff287', // Light yellow
    ];

    /**
     * Whitelist of standard score assessments that explicitly utilize the Bell Curve visualizer.
     * Archetype 1 (T-score / BASC-3), Archetype 3 (Cutoffs), and Archetype 5 (Qualitative) are excluded.
     */
    private const SUPPORTED_SLUGS = [
        'report-wisc-v',
        'report-wisc-v-first-seven',
        'report-wisc-v-ancillary',
        'wais5',
        'report-wais-iv',
        'report-wppsi-iv',
        'report-wppsi-iv-2',
        'report-wippsi-iv',
        'report-wiat-3',
        'report-wiat-4',
        'report-wiat-4-supplemental-battery',
        'report-wj-iv',
        'report-wj-cog',
        'report-wj-achievement-4th-edition',
        'wjfive_ach',
        'wjfive cognitie',
        'wjfive_giatemplate',
        'wjfirst7',
        'report-celf-5',
        'report-celf-5-page',
        'report-cas-2',
        'report-cas-2-ancillary',
        'report-casl-2',
        'report-casl-2-new',
        'report-das-ii',
        'report-kabc-ii-nu',
        'kabc2nunonverbalnew',
        'report-kabc-ii-nu-nvi',
        'report-ctopp-2-ages-4-6',
        'report-ctopp-2-ages-7-24',
        'report-ctoni-2',
        'report-dayc-2',
        'report-dp-3',
        'report-dp-4',
        'report-feifer-assessment',
        'feifer_assement',
        'report-gort-5',
        'report-ktea-3',
        'report-ktea-3-cross-domains',
        'report-rias-2',
        'report-sb-5',
        'report-taps-4',
        'report-tomal-2',
        'report-tomal-2-standard-battery',
        'ppvt-speech',
        'tvps',
        'report-wraml-3',
        'report-wraml-3-optional',
        'report-wrat-5',
        'report-wravma',
        'report-xbass',
        'xbassnew',
        'bayley4',
        'report-champ',
        'report-cefi',
        'report-cefi-self-report',
    ];

    /**
     * Determine if a template supports the Standard Score Bell Curve.
     */
    public function isSupported( string $templateSlug, string $archetype = '', ?array $config = null ): bool {
        // Explicit opt-out in template configuration.
        if ( isset( $config['has_bell_curve'] ) && false === $config['has_bell_curve'] ) {
            return false;
        }

        // Explicit opt-in in template configuration.
        if ( ! empty( $config['has_bell_curve'] ) ) {
            return true;
        }

        // Exclude non-standard-score archetypes (e.g. Archetype 1 T-score / BASC-3, Archetype 3 Cutoffs, Archetype 5 Qualitative).
        if ( in_array( $archetype, [ 't_score', 'clinical_cutoff', 'qualitative_matrix', 'static_reference' ], true ) ) {
            return false;
        }

        $normalizedSlug = strtolower( trim( $templateSlug ) );

        // Direct whitelist match
        if ( in_array( $normalizedSlug, self::SUPPORTED_SLUGS, true ) ) {
            return true;
        }

        // Suffix / variation matching (e.g. '-under-development')
        foreach ( self::SUPPORTED_SLUGS as $supported ) {
            if ( str_starts_with( $normalizedSlug, $supported ) ) {
                return true;
            }
        }

        // Generic fallback for any Archetype 2 template that hasn't disabled it
        return ( 'dual_standard_scaled' === $archetype );
    }

    /**
     * Compute positioning and styling metrics for a single Standard Score bar.
     * Formula: percentage = ((score - 40) / 120) * 100, clamped to [0, 100].
     */
    public function computeMetrics(
        float|int $standardScore,
        string $scaleKey = '',
        string $title = '',
        int $index = 0
    ): array {
        $score        = (float) $standardScore;
        $clampedScore = max( 40.0, min( 160.0, $score ) );
        $percentage   = round( ( ( $clampedScore - 40.0 ) / 120.0 ) * 100.0, 2 );
        $offsetPx     = round( $percentage / 10.0, 2 );
        $widthCss     = "calc({$percentage}% - {$offsetPx}px)";

        $lookupKey  = (int) round( $clampedScore );
        $percentile = AssessmentScorer::STANDARD_SCORE_PERCENTILES[ $lookupKey ] ?? '50';
        $color      = self::LEGACY_BAR_COLORS[ $index % 3 ];
        $sdOffset   = round( ( $score - 100.0 ) / 15.0, 2 );

        return [
            'scale_key'          => $scaleKey,
            'title'              => $title ?: $scaleKey,
            'score'              => $score,
            'clamped_score'      => $clampedScore,
            'percentage'         => $percentage,
            'offset_px'          => $offsetPx,
            'width_css'          => $widthCss,
            'percentile'         => $percentile,
            'percentile_ordinal' => AssessmentScorer::formatOrdinal( $percentile ),
            'color'              => $color,
            'sd_offset'          => $sdOffset,
        ];
    }

    /**
     * Generate HTML matching ClicReport template DOM structure.
     */
    public function generateHtml( string $templateName, array $bars ): string {
        $safeName = esc_html( $templateName );
        $html     = "<div class='summary-title bell-curve-header'>";
        $html    .= "<div class='copying-bell-curve' style='display:none;'>Copying...</div>";
        $html    .= "<div class='copied-bell-curve' style='display:none;'>Copied!</div>";
        $html    .= "<h1><span class='text-white bold mvboli'>ClickReport's</span> {$safeName} Bell Curve: ";
        $html    .= "<button type='button' class='copy-bell-curve' id='copy-bell-curve' style='float: right;'>Copy Bell Curve</button>";
        $html    .= "</h1></div>";

        $html .= "<div class='bell-curve' id='bell-curve'>";
        $html .= "<img src='/wp-content/plugins/clicreport-assessment-engine/assets/images/bell-curve-empty.svg' class='bell-curve-bg' alt='Normal Distribution Bell Curve' />";
        $html .= "<div class='bell-curve-container'>";

        foreach ( $bars as $bar ) {
            $key      = esc_attr( $bar['scale_key'] );
            $title    = esc_html( $bar['title'] );
            $score    = esc_html( (string) $bar['score'] );
            $widthCss = esc_attr( $bar['width_css'] );
            $color    = esc_attr( $bar['color'] );

            $html .= "<div class='bar {$key}_bar' style='width:{$widthCss}; background:{$color};'>{$title} ({$score})</div>";
        }

        $html .= "</div></div>";
        return $html;
    }

    /**
     * Generate a responsive, standalone, vector SVG Bell Curve graph.
     */
    public function generateSvg( string $templateName, array $bars, int $width = 800, int $height = 420 ): string {
        $safeTitle = esc_html( $templateName );

        // Bell curve baseline coordinates
        $curveTop    = 60;
        $curveBottom = 220;
        $leftPad     = 80;
        $rightPad    = 720;
        $plotWidth   = $rightPad - $leftPad; // 640px

        // Standard deviations and standard score ticks (-3 to +3 SD, scores 55 to 145)
        $ticks = [
            [ 'sd' => '-3σ', 'ss' => '55',  'pr' => '0.1%', 'pct' => ( ( 55 - 40 ) / 120 ) ],
            [ 'sd' => '-2σ', 'ss' => '70',  'pr' => '2%',   'pct' => ( ( 70 - 40 ) / 120 ) ],
            [ 'sd' => '-1σ', 'ss' => '85',  'pr' => '16%',  'pct' => ( ( 85 - 40 ) / 120 ) ],
            [ 'sd' => '0',   'ss' => '100', 'pr' => '50%',  'pct' => ( ( 100 - 40 ) / 120 ) ],
            [ 'sd' => '+1σ', 'ss' => '115', 'pr' => '84%',  'pct' => ( ( 115 - 40 ) / 120 ) ],
            [ 'sd' => '+2σ', 'ss' => '130', 'pr' => '98%',  'pct' => ( ( 130 - 40 ) / 120 ) ],
            [ 'sd' => '+3σ', 'ss' => '145', 'pr' => '99.9%','pct' => ( ( 145 - 40 ) / 120 ) ],
        ];

        // Normal distribution Gaussian curve path calculation
        $pathData = [];
        for ( $x = 0; $x <= $plotWidth; $x += 4 ) {
            $plotX = $leftPad + $x;
            $t = ( $x / $plotWidth ); // 0.0 to 1.0 (score 40 to 160)
            $z = ( $t - 0.5 ) * 6.0;  // -3.0 to +3.0
            $gaussian = exp( -0.5 * ( $z * $z ) );
            $plotY = $curveBottom - ( $gaussian * 140 );
            $pathData[] = ( 0 === $x ? "M {$plotX},{$plotY}" : "L {$plotX},{$plotY}" );
        }
        $curvePath = implode( ' ', $pathData );

        $svg  = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$width} {$height}' width='100%' height='auto' style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>";
        $svg .= "<defs>";
        $svg .= "<linearGradient id='craeCurveGrad' x1='0' y1='0' x2='0' y2='1'>";
        $svg .= "<stop offset='0%' stop-color='#3b82f6' stop-opacity='0.25' />";
        $svg .= "<stop offset='100%' stop-color='#3b82f6' stop-opacity='0.02' />";
        $svg .= "</linearGradient>";
        $svg .= "</defs>";

        // Background card
        $svg .= "<rect x='0' y='0' width='{$width}' height='{$height}' rx='8' fill='#ffffff' stroke='#e2e8f0' stroke-width='1'/>";

        // Title
        $svg .= "<text x='{$leftPad}' y='38' font-size='16' font-weight='bold' fill='#1e293b'>ClickReport&#39;s {$safeTitle} Bell Curve</text>";

        // Normal curve filled area
        $svg .= "<path d='{$curvePath} L {$rightPad},{$curveBottom} L {$leftPad},{$curveBottom} Z' fill='url(#craeCurveGrad)' />";
        // Normal curve line
        $svg .= "<path d='{$curvePath}' fill='none' stroke='#2563eb' stroke-width='2.5' />";
        // Baseline
        $svg .= "<line x1='{$leftPad}' y1='{$curveBottom}' x2='{$rightPad}' y2='{$curveBottom}' stroke='#94a3b8' stroke-width='1.5' />";

        // Grid lines & labels
        foreach ( $ticks as $tick ) {
            $tx = round( $leftPad + ( $tick['pct'] * $plotWidth ) );
            $svg .= "<line x1='{$tx}' y1='{$curveTop}' x2='{$tx}' y2='{$curveBottom}' stroke='#cbd5e1' stroke-dasharray='3 3' stroke-width='1' />";
            $svg .= "<text x='{$tx}' y='" . ( $curveBottom + 16 ) . "' font-size='11' text-anchor='middle' font-weight='600' fill='#334155'>{$tick['ss']}</text>";
            $svg .= "<text x='{$tx}' y='" . ( $curveBottom + 30 ) . "' font-size='10' text-anchor='middle' fill='#64748b'>{$tick['sd']}</text>";
            $svg .= "<text x='{$tx}' y='" . ( $curveBottom + 43 ) . "' font-size='9' text-anchor='middle' fill='#94a3b8'>{$tick['pr']}</text>";
        }

        // Overlay horizontal score bars
        $barStartY = $curveBottom + 56;
        $barHeight = 16;
        $barGap    = 6;

        foreach ( $bars as $idx => $b ) {
            $currentY   = $barStartY + ( $idx * ( $barHeight + $barGap ) );
            $scorePct   = max( 0.0, min( 1.0, ( $b['clamped_score'] - 40.0 ) / 120.0 ) );
            $bWidth     = max( 4.0, round( $scorePct * $plotWidth ) );
            $bColor     = esc_attr( $b['color'] );
            $bTitle     = esc_html( $b['title'] );
            $bScore     = esc_html( (string) $b['score'] );

            $svg .= "<g>";
            $svg .= "<rect x='{$leftPad}' y='{$currentY}' width='{$bWidth}' height='{$barHeight}' rx='3' fill='{$bColor}' stroke='#64748b' stroke-width='0.5' />";
            $svg .= "<text x='" . ( $leftPad + 6 ) . "' y='" . ( $currentY + 12 ) . "' font-size='11' font-weight='600' fill='#1e293b'>{$bTitle}: {$bScore}</text>";
            $svg .= "</g>";
        }

        $svg .= "</svg>";
        return $svg;
    }
}

