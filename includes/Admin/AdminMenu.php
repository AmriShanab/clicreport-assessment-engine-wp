<?php
/**
 * WordPress Admin Menu, Dashboard, Audit Log Viewer & Catalog Re-Seeder
 *
 * @package ClicReport\AssessmentEngine\Admin
 */

namespace ClicReport\AssessmentEngine\Admin;

use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;
use ClicReport\AssessmentEngine\Database\Seeders\ClinicalRulesSeeder;
use ClicReport\AssessmentEngine\Database\Seeders\TemplateCatalogSeeder;

defined( 'ABSPATH' ) || exit;

final class AdminMenu {

    public const TOP_SLUG   = 'clicreport-engine';
    public const AUDIT_SLUG = 'clicreport-audit-logs';

    public function __construct(
        private CapabilityManager $capabilities,
        private SettingsPage $settingsPage,
        private TemplateManagerPage $templatePage,
        private TemplateRepository $templateRepo,
        private AuditLogRepository $auditRepo
    ) {}

    /**
     * Hook Admin menu, Settings API, admin_post actions, and admin CSS.
     */
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'registerMenus' ] );
        add_action( 'admin_init', [ $this->settingsPage, 'register' ] );
        add_action( 'admin_post_crae_reseed_catalog', [ $this, 'handleReseedCatalog' ] );
        add_action( 'admin_post_crae_update_template', [ $this->templatePage, 'handleUpdate' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
    }

    public function registerMenus(): void {
        $adminCap = (string) apply_filters( 'crae_required_capability', 'manage_options', 'manage_templates' );

        add_menu_page(
            __( 'ClicReport Engine', 'clicreport-assessment-engine' ),
            __( 'ClicReport', 'clicreport-assessment-engine' ),
            $adminCap,
            self::TOP_SLUG,
            [ $this, 'renderDashboard' ],
            'dashicons-clipboard',
            58
        );

        add_submenu_page(
            self::TOP_SLUG,
            __( 'ClicReport Dashboard', 'clicreport-assessment-engine' ),
            __( 'Dashboard', 'clicreport-assessment-engine' ),
            $adminCap,
            self::TOP_SLUG,
            [ $this, 'renderDashboard' ]
        );

        add_submenu_page(
            self::TOP_SLUG,
            __( 'Clinical Templates (201)', 'clicreport-assessment-engine' ),
            __( 'Templates', 'clicreport-assessment-engine' ),
            $adminCap,
            TemplateManagerPage::PAGE_SLUG,
            [ $this->templatePage, 'render' ]
        );

        add_submenu_page(
            self::TOP_SLUG,
            __( 'ClicReport Settings', 'clicreport-assessment-engine' ),
            __( 'Settings', 'clicreport-assessment-engine' ),
            (string) apply_filters( 'crae_required_capability', 'manage_options', 'manage_settings' ),
            SettingsPage::PAGE_SLUG,
            [ $this->settingsPage, 'render' ]
        );

        add_submenu_page(
            self::TOP_SLUG,
            __( 'HIPAA/FERPA Audit Logs', 'clicreport-assessment-engine' ),
            __( 'Audit Logs', 'clicreport-assessment-engine' ),
            (string) apply_filters( 'crae_required_capability', 'manage_options', 'view_audit_logs' ),
            self::AUDIT_SLUG,
            [ $this, 'renderAuditLogs' ]
        );
    }

    public function enqueueAdminAssets( string $hookSuffix ): void {
        if ( ! str_contains( $hookSuffix, 'clicreport' ) ) {
            return;
        }

        wp_enqueue_style(
            'crae-admin',
            CRAE_PLUGIN_URL . 'assets/css/crae-admin.css',
            [],
            CRAE_VERSION
        );
    }

    public function renderDashboard(): void {
        if ( ! $this->capabilities->canManageTemplates() ) {
            wp_die( esc_html__( 'You do not have permission to view the ClicReport dashboard.', 'clicreport-assessment-engine' ), 403 );
        }

        $allCatalog     = $this->templateRepo->listCatalog( [], 1, 1 );
        $archetypeStats = [];
        foreach ( [ 't_score', 'dual_standard_scaled', 'clinical_cutoff', 'static_reference', 'qualitative_matrix' ] as $arch ) {
            $res                     = $this->templateRepo->listCatalog( [ 'archetype' => $arch ], 1, 1 );
            $archetypeStats[ $arch ] = (int) ( $res['total'] ?? 0 );
        }

        $totalTemplates = (int) ( $allCatalog['total'] ?? 0 );
        $dbVersion      = (string) get_option( 'crae_db_version', '0.0.0' );
        $recentLogs     = $this->auditRepo->paginate( 1, 5 );

        include CRAE_PLUGIN_DIR . 'resources/views/admin/dashboard.php';
    }

    public function renderAuditLogs(): void {
        if ( ! $this->capabilities->canViewAuditLogs() ) {
            wp_die( esc_html__( 'You do not have permission to view audit logs.', 'clicreport-assessment-engine' ), 403 );
        }

        $page    = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $logData = $this->auditRepo->paginate( $page, 25 );

        include CRAE_PLUGIN_DIR . 'resources/views/admin/audit-logs.php';
    }

    public function handleReseedCatalog(): void {
        if ( ! $this->capabilities->canManageTemplates() ) {
            wp_die( esc_html__( 'Insufficient permissions to re-seed the clinical catalog.', 'clicreport-assessment-engine' ), 403 );
        }

        check_admin_referer( 'crae_reseed_catalog', 'crae_nonce' );

        $seededCount = TemplateCatalogSeeder::seed();
        ClinicalRulesSeeder::seed();

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'        => self::TOP_SLUG,
                    'crae_seeded' => $seededCount,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }
}

