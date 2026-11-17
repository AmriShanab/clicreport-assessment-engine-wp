<?php
/**
 * Admin Dashboard View
 *
 * @var int                 $totalTemplates
 * @var array<string, int>  $archetypeStats
 * @var string              $dbVersion
 * @var array<string,mixed> $recentLogs
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap crae-admin-wrap">
    <h1><?php esc_html_e( 'ClicReport Assessment & Clinical Narrative Engine (v1.0)', 'clicreport-assessment-engine' ); ?></h1>

    <?php if ( isset( $_GET['crae_seeded'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %d: number of clinical templates seeded */
                        __( 'Catalog sync complete: verified/upserted %d clinical templates and rules.', 'clicreport-assessment-engine' ),
                        absint( $_GET['crae_seeded'] )
                    )
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="crae-stats-grid">
        <div class="crae-stat-card">
            <span class="crae-stat-label"><?php esc_html_e( 'Active Clinical Templates', 'clicreport-assessment-engine' ); ?></span>
            <strong class="crae-stat-value"><?php echo esc_html( (string) $totalTemplates ); ?> / 201</strong>
        </div>
        <div class="crae-stat-card">
            <span class="crae-stat-label"><?php esc_html_e( 'Database Schema Version', 'clicreport-assessment-engine' ); ?></span>
            <strong class="crae-stat-value"><?php echo esc_html( $dbVersion ); ?></strong>
        </div>
        <div class="crae-stat-card">
            <span class="crae-stat-label"><?php esc_html_e( 'REST Namespaces', 'clicreport-assessment-engine' ); ?></span>
            <strong class="crae-stat-value"><code><?php echo esc_html( CRAE_REST_NAMESPACE ); ?></code></strong>
        </div>
        <div class="crae-stat-card">
            <span class="crae-stat-label"><?php esc_html_e( 'Recent Reports Logged', 'clicreport-assessment-engine' ); ?></span>
            <strong class="crae-stat-value"><?php echo esc_html( (string) ( $recentLogs['total'] ?? 0 ) ); ?></strong>
        </div>
    </div>

    <div class="crae-card">
        <h2><?php esc_html_e( '5 Psychometric Archetype Breakdown', 'clicreport-assessment-engine' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Archetype Key', 'clicreport-assessment-engine' ); ?></th>
                    <th><?php esc_html_e( 'Engine Processor', 'clicreport-assessment-engine' ); ?></th>
                    <th><?php esc_html_e( 'Seeded Templates', 'clicreport-assessment-engine' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>t_score</code></td>
                    <td><?php esc_html_e( 'NormativeTScoreProcessor (A1)', 'clicreport-assessment-engine' ); ?></td>
                    <td><?php echo esc_html( (string) ( $archetypeStats['t_score'] ?? 0 ) ); ?></td>
                </tr>
                <tr>
                    <td><code>dual_standard_scaled</code></td>
                    <td><?php esc_html_e( 'DualStandardScaledProcessor (A2)', 'clicreport-assessment-engine' ); ?></td>
                    <td><?php echo esc_html( (string) ( $archetypeStats['dual_standard_scaled'] ?? 0 ) ); ?></td>
                </tr>
                <tr>
                    <td><code>clinical_cutoff</code></td>
                    <td><?php esc_html_e( 'ClinicalCutoffProcessor (A3)', 'clicreport-assessment-engine' ); ?></td>
                    <td><?php echo esc_html( (string) ( $archetypeStats['clinical_cutoff'] ?? 0 ) ); ?></td>
                </tr>
                <tr>
                    <td><code>static_reference</code></td>
                    <td><?php esc_html_e( 'StaticReferenceProcessor (A4)', 'clicreport-assessment-engine' ); ?></td>
                    <td><?php echo esc_html( (string) ( $archetypeStats['static_reference'] ?? 0 ) ); ?></td>
                </tr>
                <tr>
                    <td><code>qualitative_matrix</code></td>
                    <td><?php esc_html_e( 'QualitativeMatrixProcessor (A5)', 'clicreport-assessment-engine' ); ?></td>
                    <td><?php echo esc_html( (string) ( $archetypeStats['qualitative_matrix'] ?? 0 ) ); ?></td>
                </tr>
            </tbody>
        </table>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 16px;">
            <input type="hidden" name="action" value="crae_reseed_catalog" />
            <?php wp_nonce_field( 'crae_reseed_catalog', 'crae_nonce' ); ?>
            <?php submit_button( __( 'Verify & Re-Seed 201 Clinical Templates', 'clicreport-assessment-engine' ), 'primary', 'submit', false ); ?>
        </form>
    </div>

    <div class="crae-card">
        <h2><?php esc_html_e( 'Shortcode Embedding Guide', 'clicreport-assessment-engine' ); ?></h2>
        <p><?php esc_html_e( 'Embed any specific assessment form + clinical engine inside any WordPress page, Gutenberg block, or Avada layout:', 'clicreport-assessment-engine' ); ?></p>
        <p><code>[clicreport_template slug="report-basc-3-social-emotional"]</code></p>
        <p><?php esc_html_e( 'Or embed the interactive multi-template clinical sandbox:', 'clicreport-assessment-engine' ); ?></p>
        <p><code>[clicreport_sandbox]</code></p>
    </div>
</div>

