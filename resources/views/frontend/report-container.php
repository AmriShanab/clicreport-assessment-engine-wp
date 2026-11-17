<?php
/**
 * Frontend Clinical Assessment Shortcode View
 *
 * @var array<string, mixed>              $template
 * @var array<int, array<string, mixed>>  $scales
 * @var array<int, array<string, mixed>>  $catalogItems
 * @var string                            $customTitle
 * @var bool                              $isSandbox
 */

defined( 'ABSPATH' ) || exit;

$uid          = 'crae-' . wp_generate_uuid4();
$templateSlug = (string) $template['template_slug'];
$archetype    = (string) ( $template['archetype'] ?? 't_score' );
$sampleScales = ! empty( $scales ) ? array_slice( $scales, 0, 12 ) : [
    [
        'scale_key' => 'externalizing',
        'title'     => __( 'Primary Composite / Scale Score', 'clicreport-assessment-engine' ),
        'scale_type' => 'clinical',
    ],
];
?>
<div class="crae-shortcode-shell" id="<?php echo esc_attr( $uid ); ?>" data-template-slug="<?php echo esc_attr( $templateSlug ); ?>">
    <div class="crae-shortcode-header">
        <h3><?php echo esc_html( $customTitle ); ?></h3>
        <span class="crae-archetype-pill"><?php echo esc_html( $archetype ); ?></span>
    </div>

    <form class="crae-assessment-form">
        <?php if ( $isSandbox && ! empty( $catalogItems ) ) : ?>
            <div class="crae-field" style="margin-bottom: 14px;">
                <label for="<?php echo esc_attr( $uid ); ?>-tpl"><?php esc_html_e( 'Select Clinical Template (Catalog Sample)', 'clicreport-assessment-engine' ); ?></label>
                <select id="<?php echo esc_attr( $uid ); ?>-tpl" class="crae-sandbox-select" name="template_name">
                    <?php foreach ( $catalogItems as $item ) : ?>
                        <option value="<?php echo esc_attr( (string) $item['template_slug'] ); ?>" <?php selected( $templateSlug, (string) $item['template_slug'] ); ?>>
                            <?php echo esc_html( (string) $item['template_name'] . ' (' . (string) $item['archetype'] . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="crae-form-grid">
            <div class="crae-field">
                <label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Examinee First Name', 'clicreport-assessment-engine' ); ?></label>
                <input id="<?php echo esc_attr( $uid ); ?>-name" type="text" name="first_name" value="Alex" required />
            </div>
            <div class="crae-field">
                <label for="<?php echo esc_attr( $uid ); ?>-pronoun"><?php esc_html_e( 'Pronouns', 'clicreport-assessment-engine' ); ?></label>
                <select id="<?php echo esc_attr( $uid ); ?>-pronoun" name="gender">
                    <option value="she/her">she/her</option>
                    <option value="he/him">he/him</option>
                    <option value="they/them">they/them</option>
                    <option value="ze/hir">ze/hir</option>
                    <option value="ze/zir">ze/zir</option>
                    <option value="xey/xem">xey/xem</option>
                </select>
            </div>
            <div class="crae-field">
                <label for="<?php echo esc_attr( $uid ); ?>-rater"><?php esc_html_e( 'Primary Informant', 'clicreport-assessment-engine' ); ?></label>
                <input id="<?php echo esc_attr( $uid ); ?>-rater" type="text" name="informant_one" value="Ms. Morgan" />
            </div>
            <div class="crae-field">
                <label for="<?php echo esc_attr( $uid ); ?>-rel"><?php esc_html_e( 'Informant Relationship', 'clicreport-assessment-engine' ); ?></label>
                <select id="<?php echo esc_attr( $uid ); ?>-rel" name="informant_one_relationship">
                    <option value="teacher"><?php esc_html_e( 'Teacher', 'clicreport-assessment-engine' ); ?></option>
                    <option value="mother"><?php esc_html_e( 'Mother', 'clicreport-assessment-engine' ); ?></option>
                    <option value="father"><?php esc_html_e( 'Father', 'clicreport-assessment-engine' ); ?></option>
                    <option value="self"><?php esc_html_e( 'Self', 'clicreport-assessment-engine' ); ?></option>
                </select>
            </div>
        </div>

        <table class="crae-scales-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Scale / Domain', 'clicreport-assessment-engine' ); ?></th>
                    <th><?php esc_html_e( 'Score Type', 'clicreport-assessment-engine' ); ?></th>
                    <th><?php esc_html_e( 'Score Value', 'clicreport-assessment-engine' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $sampleScales as $scale ) :
                    $fieldKey = sanitize_key( (string) $scale['scale_key'] ) . '_0';
                ?>
                    <tr>
                        <td><?php echo esc_html( (string) $scale['title'] ); ?></td>
                        <td><code><?php echo esc_html( (string) ( $scale['scale_type'] ?? 'clinical' ) ); ?></code></td>
                        <td><input type="number" step="0.1" min="0" max="300" name="<?php echo esc_attr( $fieldKey ); ?>" value="55" /></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="crae-actions-bar">
            <button type="submit" class="crae-btn"><?php esc_html_e( 'Generate Clinical Narrative', 'clicreport-assessment-engine' ); ?></button>
            <button type="button" class="crae-btn crae-btn-secondary crae-copy-btn" style="display:none;"><?php esc_html_e( 'Copy Narrative Text', 'clicreport-assessment-engine' ); ?></button>
            <span class="crae-status-text"></span>
        </div>
    </form>

    <div class="crae-report-output" style="display:none;"></div>
</div>

