<?php
/**
 * WordPress Admin Page for Inspecting & Editing the 201 Clinical Templates
 *
 * @package ClicReport\AssessmentEngine\Admin
 */

namespace ClicReport\AssessmentEngine\Admin;

use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;

defined( 'ABSPATH' ) || exit;

final class TemplateManagerPage {

    public const PAGE_SLUG = 'clicreport-templates';

    public function __construct(
        private CapabilityManager $capabilities,
        private TemplateRepository $templateRepo,
        private ScaleRepository $scaleRepo,
        private NarrativeRuleRepository $narrativeRepo
    ) {}

    /**
     * Handle POST template metadata updates safely with capability & nonce checks.
     */
    public function handleUpdate(): void {
        if ( ! $this->capabilities->canManageTemplates() ) {
            wp_die( esc_html__( 'Insufficient permissions to modify clinical templates.', 'clicreport-assessment-engine' ), 403 );
        }

        check_admin_referer( 'crae_update_template', 'crae_nonce' );

        $slug = sanitize_key( (string) ( $_POST['template_slug'] ?? '' ) );
        $existing = '' !== $slug ? $this->templateRepo->findBySlug( $slug ) : null;

        if ( null === $existing ) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'         => self::PAGE_SLUG,
                        'crae_notice'  => 'not_found',
                    ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        $updated = [
            'template_slug'        => $existing['template_slug'],
            'template_name'        => sanitize_text_field( wp_unslash( (string) ( $_POST['template_name'] ?? $existing['template_name'] ) ) ),
            'publisher'            => sanitize_text_field( wp_unslash( (string) ( $_POST['publisher'] ?? ( $existing['publisher'] ?? '' ) ) ) ),
            'category'             => sanitize_text_field( wp_unslash( (string) ( $_POST['category'] ?? $existing['category'] ) ) ),
            'archetype'            => $existing['archetype'],
            'default_score_system' => $existing['default_score_system'],
            'intro_narrative'      => wp_kses_post( wp_unslash( (string) ( $_POST['intro_narrative'] ?? '' ) ) ),
            'config_json'          => $existing['config_json'],
            'is_active'            => ! empty( $_POST['is_active'] ) ? 1 : 0,
        ];

        $this->templateRepo->upsert( $updated );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'        => self::PAGE_SLUG,
                    'edit'        => $slug,
                    'crae_notice' => 'updated',
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Render either the template catalog list or a single template inspector/editor.
     */
    public function render(): void {
        if ( ! $this->capabilities->canManageTemplates() ) {
            wp_die( esc_html__( 'Insufficient permissions to inspect clinical templates.', 'clicreport-assessment-engine' ), 403 );
        }

        $editSlug = isset( $_GET['edit'] ) ? sanitize_key( (string) wp_unslash( $_GET['edit'] ) ) : '';
        if ( '' !== $editSlug ) {
            $this->renderSingleEditor( $editSlug );
            return;
        }

        $page      = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $archetype = isset( $_GET['archetype'] ) ? sanitize_key( (string) wp_unslash( $_GET['archetype'] ) ) : '';
        $search    = isset( $_GET['s'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['s'] ) ) : '';

        $catalog = $this->templateRepo->listCatalog(
            [
                'archetype' => $archetype,
                'search'    => $search,
            ],
            $page,
            25
        );

        echo '<div class="wrap crae-admin-wrap">';
        echo '<h1>' . esc_html__( 'Clinical Assessment Templates Catalog', 'clicreport-assessment-engine' ) . '</h1>';

        echo '<form method="get" class="crae-filter-bar">';
        echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_SLUG ) . '" />';
        echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search templates or publishers...', 'clicreport-assessment-engine' ) . '" /> ';
        echo '<select name="archetype">';
        echo '<option value="">' . esc_html__( 'All Archetypes', 'clicreport-assessment-engine' ) . '</option>';

        $archetypes = [
            't_score'              => 'A1: Normative T-Score',
            'dual_standard_scaled' => 'A2: Dual Standard/Scaled',
            'clinical_cutoff'      => 'A3: Clinical Cutoff',
            'static_reference'     => 'A4: Static Reference',
            'qualitative_matrix'   => 'A5: Qualitative Matrix',
        ];
        foreach ( $archetypes as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $archetype, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select> ';
        submit_button( __( 'Filter', 'clicreport-assessment-engine' ), 'secondary', '', false );
        echo '</form>';

        echo '<p class="description">' . esc_html( sprintf( __( 'Showing %1$d matching templates (Total: %2$d).', 'clicreport-assessment-engine' ), count( $catalog['items'] ), (int) $catalog['total'] ) ) . '</p>';

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Template Name', 'clicreport-assessment-engine' ) . '</th>';
        echo '<th>' . esc_html__( 'Slug / Shortcode', 'clicreport-assessment-engine' ) . '</th>';
        echo '<th>' . esc_html__( 'Archetype', 'clicreport-assessment-engine' ) . '</th>';
        echo '<th>' . esc_html__( 'Category', 'clicreport-assessment-engine' ) . '</th>';
        echo '<th>' . esc_html__( 'Publisher', 'clicreport-assessment-engine' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'clicreport-assessment-engine' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $catalog['items'] ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No matching clinical templates found.', 'clicreport-assessment-engine' ) . '</td></tr>';
        } else {
            foreach ( $catalog['items'] as $row ) {
                $slug    = (string) $row['template_slug'];
                $editUrl = add_query_arg(
                    [
                        'page' => self::PAGE_SLUG,
                        'edit' => $slug,
                    ],
                    admin_url( 'admin.php' )
                );

                echo '<tr>';
                echo '<td><strong>' . esc_html( (string) $row['template_name'] ) . '</strong></td>';
                echo '<td><code>[clicreport_template slug="' . esc_attr( $slug ) . '"]</code></td>';
                echo '<td><span class="crae-badge">' . esc_html( (string) $row['archetype'] ) . '</span></td>';
                echo '<td>' . esc_html( (string) $row['category'] ) . '</td>';
                echo '<td>' . esc_html( (string) ( $row['publisher'] ?? '—' ) ) . '</td>';
                echo '<td><a class="button button-small" href="' . esc_url( $editUrl ) . '">' . esc_html__( 'Inspect / Edit', 'clicreport-assessment-engine' ) . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        $totalPages = (int) ( $catalog['total_pages'] ?? 1 );
        if ( $totalPages > 1 ) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo wp_kses_post(
                (string) paginate_links(
                    [
                        'base'    => add_query_arg( 'paged', '%#%' ),
                        'format'  => '',
                        'current' => $page,
                        'total'   => $totalPages,
                    ]
                )
            );
            echo '</div></div>';
        }

        echo '</div>';
    }

    private function renderSingleEditor( string $slug ): void {
        $template = $this->templateRepo->findBySlug( $slug );
        if ( null === $template ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Template not found.', 'clicreport-assessment-engine' ) . '</p></div></div>';
            return;
        }

        $tplId     = (int) $template['id'];
        $sections  = $this->templateRepo->getSections( $tplId );
        $scales    = $this->scaleRepo->getByTemplate( $tplId );
        $decisions = $this->narrativeRepo->getDecisionOptions( $tplId );

        $backUrl = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) );

        echo '<div class="wrap crae-admin-wrap">';
        echo '<h1>' . esc_html( (string) $template['template_name'] ) . '</h1>';
        echo '<p><a href="' . esc_url( $backUrl ) . '">&larr; ' . esc_html__( 'Back to 201 Template Catalog', 'clicreport-assessment-engine' ) . '</a></p>';

        if ( isset( $_GET['crae_notice'] ) && 'updated' === $_GET['crae_notice'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Template metadata saved successfully.', 'clicreport-assessment-engine' ) . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="crae-card">';
        echo '<input type="hidden" name="action" value="crae_update_template" />';
        echo '<input type="hidden" name="template_slug" value="' . esc_attr( $slug ) . '" />';
        wp_nonce_field( 'crae_update_template', 'crae_nonce' );

        echo '<table class="form-table"><tbody>';
        echo '<tr><th><label for="crae_tpl_name">' . esc_html__( 'Template Name', 'clicreport-assessment-engine' ) . '</label></th>';
        echo '<td><input id="crae_tpl_name" type="text" name="template_name" value="' . esc_attr( (string) $template['template_name'] ) . '" class="regular-text" required /></td></tr>';

        echo '<tr><th><label for="crae_tpl_pub">' . esc_html__( 'Publisher', 'clicreport-assessment-engine' ) . '</label></th>';
        echo '<td><input id="crae_tpl_pub" type="text" name="publisher" value="' . esc_attr( (string) ( $template['publisher'] ?? '' ) ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th><label for="crae_tpl_cat">' . esc_html__( 'Category', 'clicreport-assessment-engine' ) . '</label></th>';
        echo '<td><input id="crae_tpl_cat" type="text" name="category" value="' . esc_attr( (string) $template['category'] ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Archetype / Scoring', 'clicreport-assessment-engine' ) . '</th>';
        echo '<td><code>' . esc_html( (string) $template['archetype'] ) . '</code> / <code>' . esc_html( (string) $template['default_score_system'] ) . '</code></td></tr>';

        echo '<tr><th><label for="crae_tpl_intro">' . esc_html__( 'Intro Narrative', 'clicreport-assessment-engine' ) . '</label></th>';
        echo '<td><textarea id="crae_tpl_intro" name="intro_narrative" rows="4" class="large-text">' . esc_textarea( (string) ( $template['intro_narrative'] ?? '' ) ) . '</textarea></td></tr>';

        echo '<tr><th>' . esc_html__( 'Active', 'clicreport-assessment-engine' ) . '</th>';
        echo '<td><label><input type="checkbox" name="is_active" value="1" ' . checked( ! empty( $template['is_active'] ), true, false ) . ' /> ' . esc_html__( 'Available for report generation', 'clicreport-assessment-engine' ) . '</label></td></tr>';
        echo '</tbody></table>';

        submit_button( __( 'Save Template Metadata', 'clicreport-assessment-engine' ) );
        echo '</form>';

        echo '<h2>' . esc_html( sprintf( __( 'Configured Sections (%d) & Scales (%d)', 'clicreport-assessment-engine' ), count( $sections ), count( $scales ) ) ) . '</h2>';
        if ( ! empty( $scales ) ) {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            echo '<th>' . esc_html__( 'Scale Key', 'clicreport-assessment-engine' ) . '</th>';
            echo '<th>' . esc_html__( 'Title', 'clicreport-assessment-engine' ) . '</th>';
            echo '<th>' . esc_html__( 'Type', 'clicreport-assessment-engine' ) . '</th>';
            echo '<th>' . esc_html__( 'Rater Scope', 'clicreport-assessment-engine' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $scales as $scale ) {
                echo '<tr>';
                echo '<td><code>' . esc_html( (string) $scale['scale_key'] ) . '</code></td>';
                echo '<td>' . esc_html( (string) $scale['title'] ) . '</td>';
                echo '<td>' . esc_html( (string) $scale['scale_type'] ) . '</td>';
                echo '<td>' . esc_html( (string) $scale['rater_restriction'] ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="description">' . esc_html__( 'This template uses archetype defaults or qualitative decision rules.', 'clicreport-assessment-engine' ) . '</p>';
        }

        if ( ! empty( $decisions ) ) {
            echo '<h3>' . esc_html( sprintf( __( 'Decision Options (%d)', 'clicreport-assessment-engine' ), count( $decisions ) ) ) . '</h3>';
        }

        echo '</div>';
    }
}

