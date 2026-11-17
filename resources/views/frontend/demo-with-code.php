<?php
/**
 * Live Plugin + Code Demo View ([clicreport_demo])
 *
 * @var array<int, array<string, mixed>> $catalogItems
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="crae-demo-wrap">
    <div class="crae-demo-banner">
        <div>
            <h2><?php esc_html_e( 'ClicReport v1.0 — Live WordPress Plugin & API Code Demo', 'clicreport-assessment-engine' ); ?></h2>
            <p><?php esc_html_e( 'Interactive demo showing how frontend views call /wp-json/clicreport/v1/reports/generate across all 5 clinical archetypes.', 'clicreport-assessment-engine' ); ?></p>
        </div>
        <span class="crae-archetype-pill"><?php echo esc_html( count( $catalogItems ) . '+ Templates Ready' ); ?></span>
    </div>

    <div class="crae-demo-presets">
        <button type="button" class="crae-preset-btn is-active" data-preset="t_score">A1: BASC-3 (T-Score)</button>
        <button type="button" class="crae-preset-btn" data-preset="dual_standard_scaled">A2: WISC-V (Standard/Scaled)</button>
        <button type="button" class="crae-preset-btn" data-preset="clinical_cutoff">A3: SCQ (Clinical Cutoff)</button>
        <button type="button" class="crae-preset-btn" data-preset="static_reference">A4: ABAS-3 (Static Ref)</button>
        <button type="button" class="crae-preset-btn" data-preset="qualitative_matrix">A5: SLD (Eligibility Matrix)</button>
    </div>

    <div class="crae-demo-grid">
        <div class="crae-demo-left">
            <form class="crae-demo-form">
                <div class="crae-field" style="margin-bottom:12px;">
                    <label><?php esc_html_e( 'Template Slug (template_name)', 'clicreport-assessment-engine' ); ?></label>
                    <select class="crae-demo-template-select" name="template_name">
                        <?php foreach ( $catalogItems as $item ) : ?>
                            <option value="<?php echo esc_attr( (string) $item['template_slug'] ); ?>">
                                <?php echo esc_html( (string) $item['template_name'] . ' [' . (string) $item['archetype'] . ']' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="crae-form-grid">
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Examinee First Name', 'clicreport-assessment-engine' ); ?></label>
                        <input type="text" name="first_name" value="Alex" required />
                    </div>
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Pronouns', 'clicreport-assessment-engine' ); ?></label>
                        <select name="gender">
                            <option value="she/her">she/her</option>
                            <option value="he/him">he/him</option>
                            <option value="they/them">they/them</option>
                            <option value="ze/hir">ze/hir</option>
                            <option value="ze/zir">ze/zir</option>
                            <option value="xey/xem">xey/xem</option>
                        </select>
                    </div>
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Rater 1 Name', 'clicreport-assessment-engine' ); ?></label>
                        <input type="text" name="informant_one" value="Ms. Rivera" />
                    </div>
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Rater 1 Role', 'clicreport-assessment-engine' ); ?></label>
                        <input type="text" name="informant_one_relationship" value="Teacher" />
                    </div>
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Rater 2 Name (Optional)', 'clicreport-assessment-engine' ); ?></label>
                        <input type="text" name="informant_two" value="Jordan" />
                    </div>
                    <div class="crae-field">
                        <label><?php esc_html_e( 'Rater 2 Role', 'clicreport-assessment-engine' ); ?></label>
                        <input type="text" name="informant_two_relationship" value="Mother" />
                    </div>
                </div>

                <div class="crae-form-grid crae-demo-dynamic-fields"></div>

                <div class="crae-actions-bar">
                    <button type="submit" class="crae-btn"><?php esc_html_e( 'Run Live Plugin Engine', 'clicreport-assessment-engine' ); ?></button>
                    <span class="crae-status-text crae-demo-status"></span>
                </div>
            </form>

            <div class="crae-score-cards"></div>
            <div class="crae-report-output crae-demo-narrative"></div>
        </div>

        <div class="crae-demo-right">
            <div class="crae-code-tabs">
                <button type="button" class="crae-code-tab is-active" data-tab="js">Frontend JS Code</button>
                <button type="button" class="crae-code-tab" data-tab="request">Request JSON</button>
                <button type="button" class="crae-code-tab" data-tab="response">Live Response JSON</button>
                <button type="button" class="crae-code-tab" data-tab="embed">Shortcode / PHP</button>
            </div>
            <div class="crae-code-toolbar">
                <span class="crae-code-title">Frontend JS -&gt; POST /wp-json/clicreport/v1/reports/generate</span>
                <button type="button" class="crae-copy-code-btn">Copy</button>
            </div>
            <pre class="crae-code-pre"></pre>
        </div>
    </div>
</div>

