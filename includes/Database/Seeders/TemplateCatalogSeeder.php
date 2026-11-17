<?php
/**
 * Seeds all 201 Clinical Assessment Templates into WordPress MySQL.
 *
 * @package ClicReport\AssessmentEngine\Database\Seeders
 */

namespace ClicReport\AssessmentEngine\Database\Seeders;

use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;

defined( 'ABSPATH' ) || exit;

final class TemplateCatalogSeeder {

    /**
     * Load bundled 201-template JSON and upsert into {$wpdb->prefix}crae_templates.
     */
    public static function seed(): int {
        global $wpdb;

        $file = CRAE_PLUGIN_DIR . 'config/seed-data/templates.json';
        if ( ! is_readable( $file ) ) {
            return 0;
        }

        $templates = wp_json_file_decode( $file, [ 'associative' => true ] );
        if ( ! is_array( $templates ) ) {
            return 0;
        }

        $repo  = new TemplateRepository( $wpdb );
        $count = 0;

        $repo->beginTransaction();
        try {
            foreach ( $templates as $entry ) {
                $repo->upsert( $entry );
                $count++;
            }
            $repo->commit();
        } catch ( \Throwable $e ) {
            $repo->rollback();
            throw $e;
        }

        return $count;
    }
}