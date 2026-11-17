<?php

/**
 * WordPress Frontend Asset Loader & craeConfig Localization
 *
 * @package ClicReport\AssessmentEngine\Frontend
 */

namespace ClicReport\AssessmentEngine\Frontend;

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
        ];
    }
}
