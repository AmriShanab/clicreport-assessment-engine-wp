<?php

/**
 * WordPress Frontend Asset Loader & craeConfig Localization
 *
 * @package ClicReport\AssessmentEngine\Frontend
 */

namespace ClicReport\AssessmentEngine\Frontend;

use ClicReport\AssessmentEngine\Services\Scoring\AssessmentScorer;

defined('ABSPATH') || exit;

final class AssetLoader
{

    public const SCRIPT_HANDLE      = 'crae-api-client';
    public const STYLE_HANDLE       = 'crae-clinical-report';
    public const DEMO_SCRIPT_HANDLE = 'crae-demo';
    public const DEMO_STYLE_HANDLE  = 'crae-demo-style';

    /**
     * Hook script/style registration on wp_enqueue_scripts.
     */
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    /**
     * Register frontend assets cleanly without loading globally unless needed.
     */
    public function registerAssets(): void
    {
        wp_register_style(
            self::STYLE_HANDLE,
            CRAE_PLUGIN_URL . 'assets/css/crae-clinical-report.css',
            [],
            CRAE_VERSION
        );

        wp_register_script(
            self::SCRIPT_HANDLE,
            CRAE_PLUGIN_URL . 'assets/js/crae-api-client.js',
            [],
            CRAE_VERSION,
            false
        );

        wp_localize_script(
            self::SCRIPT_HANDLE,
            'craeConfig',
            $this->buildLocalizedConfig()
        );

        wp_register_style(
            self::DEMO_STYLE_HANDLE,
            CRAE_PLUGIN_URL . 'assets/css/crae-demo.css',
            [self::STYLE_HANDLE],
            CRAE_VERSION
        );

        wp_register_script(
            self::DEMO_SCRIPT_HANDLE,
            CRAE_PLUGIN_URL . 'assets/js/crae-demo.js',
            [self::SCRIPT_HANDLE],
            CRAE_VERSION,
            true
        );
    }

    /**
     * Enqueue assets on-demand when a shortcode or template renders.
     */
    public function enqueueFrontendAssets(): void
    {
        if (! wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
            $this->registerAssets();
        }

        wp_enqueue_style(self::STYLE_HANDLE);
        wp_enqueue_script(self::SCRIPT_HANDLE);
    }

    /**
     * Enqueue interactive code + API showcase assets for [clicreport_demo].
     */
    public function enqueueDemoAssets(): void
    {
        $this->enqueueFrontendAssets();

        if (! wp_script_is(self::DEMO_SCRIPT_HANDLE, 'registered')) {
            $this->registerAssets();
        }

        wp_enqueue_style(self::DEMO_STYLE_HANDLE);
        wp_enqueue_script(self::DEMO_SCRIPT_HANDLE);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLocalizedConfig(): array
    {
        $psiveyToken = '';
        if (is_user_logged_in()) {
            try {
                $user = wp_get_current_user();
                if ($user->exists() && function_exists('crae')) {
                    $tokenService = crae()->container()->get(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class);
                    $issued       = $tokenService->issueForUser($user);
                    $psiveyToken  = (string) ($issued['token'] ?? '');
                }
            } catch (\Throwable) {
                $psiveyToken = '';
            }
        }

        return [
            'ajaxUrl'        => esc_url_raw(admin_url('admin-ajax.php')),
            'ajaxAction'     => 'crae_generate_report',
            'ajaxNonce'      => wp_create_nonce('crae_ajax_nonce'),
            'psiveyToken'    => $psiveyToken,
            'restUrl'        => esc_url_raw(rest_url(CRAE_REST_NAMESPACE . '/reports/generate')),
            'psiveyTokenUrl' => esc_url_raw(rest_url(CRAE_PSIVEY_REST_NAMESPACE . '/generate-token')),
            'imagesUrl'      => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/'),
            'images'         => [
                'copyTable'              => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-table.png'),
                'copyChart'              => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-chart.png'),
                'copyNarrative'          => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-narrative.png'),
                'copyNarrativeSummary'   => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-narrative-summary.jpg'),
                'copyPerformanceSummary' => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-performance-summary.png'),
                'copyPictureOfTable'     => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-picture-of-table.jpg'),
                'copyPictureOfChart'     => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/copy-picture-of-chart.jpg'),
                'bellCurveEmpty'         => esc_url_raw(CRAE_PLUGIN_URL . 'assets/images/bell-curve-empty.jpg'),
            ],
            'isLoggedIn'     => is_user_logged_in(),
            'version'        => CRAE_VERSION,
            'scoring' => [
                'standardPercentiles' => AssessmentScorer::STANDARD_SCORE_PERCENTILES,
                'scaledPercentiles'   => AssessmentScorer::SCALED_SCORE_PERCENTILES,
                'standardRanges'      => [
                    ['max' => 69,  'label' => 'Significantly Below Average', 'stars' => '**', 'color' => '#e06c6c'],
                    ['max' => 79,  'label' => 'Well Below Average',          'stars' => '*',  'color' => '#ea9999'],
                    ['max' => 84,  'label' => 'Below Average',               'stars' => '*',  'color' => '#ffe699'],
                    ['max' => 89,  'label' => 'Low Average',                 'stars' => '',   'color' => '#ffe699'],
                    ['max' => 110, 'label' => 'Average',                     'stars' => '',   'color' => ''],
                    ['max' => 115, 'label' => 'High Average',                'stars' => '',   'color' => '#d9ead3'],
                    ['max' => 120, 'label' => 'Above Average',               'stars' => '',   'color' => '#d9ead3'],
                    ['max' => 130, 'label' => 'Well Above Average',          'stars' => '',   'color' => '#b6d7a8'],
                    ['max' => 999, 'label' => 'Significantly Above Average', 'stars' => '',   'color' => '#93c47d'],
                ],
                'scaledRanges'        => [
                    ['max' => 3,   'label' => 'Significantly Below Average', 'stars' => '**'],
                    ['max' => 5,   'label' => 'Well Below Average',          'stars' => '*'],
                    ['max' => 6,   'label' => 'Below Average',               'stars' => '*'],
                    ['max' => 7,   'label' => 'Low Average',                 'stars' => ''],
                    ['max' => 12,  'label' => 'Average',                     'stars' => ''],
                    ['max' => 13,  'label' => 'High Average',                'stars' => ''],
                    ['max' => 14,  'label' => 'Above Average',               'stars' => ''],
                    ['max' => 16,  'label' => 'Well Above Average',          'stars' => ''],
                    ['max' => 999, 'label' => 'Significantly Above Average', 'stars' => ''],
                ],
            ],
        ];
    }
}
