<?php
/**
 * WordPress Shortcode Manager
 *
 * Registers:
 *   [clicreport_template slug="report-basc-3-social-emotional" title=""]
 *   [clicreport_sandbox]
 *   [clicreport_demo]
 *
 * @package ClicReport\AssessmentEngine\Frontend
 */

namespace ClicReport\AssessmentEngine\Frontend;

use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;

defined( 'ABSPATH' ) || exit;

final class ShortcodeManager {

    public function __construct(
        private CapabilityManager $capabilities,
        private TemplateRepository $templateRepo,
        private ScaleRepository $scaleRepo,
        private AssetLoader $assets
    ) {}

    public function register(): void {
        add_shortcode( 'clicreport_template', [ $this, 'renderTemplateShortcode' ] );
        add_shortcode( 'clicreport_sandbox', [ $this, 'renderSandboxShortcode' ] );
        add_shortcode( 'clicreport_demo', [ $this, 'renderDemoShortcode' ] );
    }

    /**
     * Render a single clinical assessment template form + live generator.
     *
     * @param array<string, string>|string $atts
     */
    public function renderTemplateShortcode( array|string $atts = [] ): string {
        $atts = shortcode_atts(
            [
                'slug'  => 'report-basc-3-social-emotional',
                'title' => '',
            ],
            is_array( $atts ) ? $atts : [],
            'clicreport_template'
        );

        $this->assets->enqueueFrontendAssets();

        if ( ! $this->capabilities->canViewTemplates() ) {
            return '<div class="crae-notice-auth">' . esc_html__( 'Please sign in with an authorized account to generate clinical reports.', 'clicreport-assessment-engine' ) . '</div>';
        }

        $slug     = sanitize_key( (string) $atts['slug'] );
        $template = $this->templateRepo->findBySlug( $slug );

        if ( null === $template || empty( $template['is_active'] ) ) {
            return '<div class="crae-notice-auth">' . esc_html(
                sprintf( __( 'Assessment template "%s" is unavailable or inactive.', 'clicreport-assessment-engine' ), $slug )
            ) . '</div>';
        }

        $tplId       = (int) $template['id'];
        $scales      = $this->scaleRepo->getByTemplate( $tplId );
        $customTitle = '' !== trim( (string) $atts['title'] )
            ? sanitize_text_field( (string) $atts['title'] )
            : (string) $template['template_name'];
        $catalogItems = [];
        $isSandbox    = false;

        ob_start();
        include CRAE_PLUGIN_DIR . 'resources/views/frontend/report-container.php';
        return (string) ob_get_clean();
    }

    /**
     * Render an interactive multi-template sandbox across all active templates.
     *
     * @param array<string, string>|string $atts
     */
    public function renderSandboxShortcode( array|string $atts = [] ): string {
        $atts = shortcode_atts(
            [
                'slug'      => 'report-basc-3-social-emotional',
                'archetype' => '',
            ],
            is_array( $atts ) ? $atts : [],
            'clicreport_sandbox'
        );

        $this->assets->enqueueFrontendAssets();

        if ( ! $this->capabilities->canViewTemplates() ) {
            return '<div class="crae-notice-auth">' . esc_html__( 'Please sign in with an authorized account to access the assessment sandbox.', 'clicreport-assessment-engine' ) . '</div>';
        }

        $catalog = $this->templateRepo->listCatalog(
            [
                'archetype' => sanitize_key( (string) $atts['archetype'] ),
            ],
            1,
            100
        );

        $catalogItems = is_array( $catalog['items'] ?? null ) ? $catalog['items'] : [];
        $slug         = sanitize_key( (string) $atts['slug'] );
        $template     = $this->templateRepo->findBySlug( $slug );
        if ( null === $template && ! empty( $catalogItems ) ) {
            $template = $catalogItems[0];
            $slug     = (string) $template['template_slug'];
        }

        if ( null === $template ) {
            return '<div class="crae-notice-auth">' . esc_html__( 'No active clinical templates found.', 'clicreport-assessment-engine' ) . '</div>';
        }

        $scales      = $this->scaleRepo->getByTemplate( (int) $template['id'] );
        $customTitle = (string) $template['template_name'];
        $isSandbox   = true;

        ob_start();
        include CRAE_PLUGIN_DIR . 'resources/views/frontend/report-container.php';
        return (string) ob_get_clean();
    }

    /**
     * Render interactive plugin + live API/code showcase ([clicreport_demo]).
     *
     * @param array<string, string>|string $atts
     */
    public function renderDemoShortcode( array|string $atts = [] ): string {
        $this->assets->enqueueDemoAssets();

        if ( ! $this->capabilities->canViewTemplates() ) {
            return '<div class="crae-notice-auth">' . esc_html__( 'Please sign in with an authorized account to view the ClicReport live demo.', 'clicreport-assessment-engine' ) . '</div>';
        }

        $catalogItems = [];
        for ( $p = 1; $p <= 3; $p++ ) {
            $pageRes = $this->templateRepo->listCatalog( [], $p, 100 );
            $items   = is_array( $pageRes['items'] ?? null ) ? $pageRes['items'] : [];
            if ( empty( $items ) ) {
                break;
            }
            foreach ( $items as $item ) {
                $slugKey = (string) ( $item['template_slug'] ?? '' );
                if ( '' !== $slugKey ) {
                    $catalogItems[ $slugKey ] = $item;
                }
            }
            if ( $p >= (int) ( $pageRes['total_pages'] ?? 1 ) ) {
                break;
            }
        }
        $catalogItems = array_values( $catalogItems );

        ob_start();
        include CRAE_PLUGIN_DIR . 'resources/views/frontend/demo-with-code.php';
        return (string) ob_get_clean();
    }
}

