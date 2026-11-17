<?php
/**
 * Admin Settings View (WordPress Settings API)
 *
 * @var string $optionGroup
 * @var string $pageSlug
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap crae-admin-wrap">
    <h1><?php esc_html_e( 'ClicReport Engine & Psivey Settings', 'clicreport-assessment-engine' ); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php" class="crae-card">
        <?php
        settings_fields( $optionGroup );
        do_settings_sections( $pageSlug );
        submit_button( __( 'Save ClicReport Settings', 'clicreport-assessment-engine' ) );
        ?>
    </form>
</div>

