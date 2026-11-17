<?php
/**
 * Integration Regression Suite: Full 201-Template Database Engine Execution
 *
 * Iterates through every template in config/seed-data/templates.json, executes
 * DatabaseReportEngine::generate() with synthetic clinical inputs & XSS probes,
 * verifies clean non-PII HIPAA/FERPA audit logging, and checks latency.
 *
 * @package ClicReport\AssessmentEngine\Tests\Integration
 */

declare(strict_types=1);

use ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine;

final class TemplateCatalogRegressionTest {

    public function run(): void {
        global $wpdb;

        $wpdb->auditLogs = [];
        $seedTemplates   = json_decode(
            (string) file_get_contents( CRAE_PLUGIN_DIR . 'config/seed-data/templates.json' ),
            true
        );

        CraeAssert::same( 201, count( $seedTemplates ), 'Seed catalog contains exactly 201 templates' );

        /** @var DatabaseReportEngine $engine */
        $engine     = crae()->container()->get( DatabaseReportEngine::class );
        $maxLatency = 0.0;

        foreach ( $seedTemplates as $entry ) {
            $slug = (string) $entry['template_slug'];

            $payload = [
                'template_name' => $slug,
                'first_name'    => '<script>alert("xss")</script>Taylor',
                'gender'        => 'they/them',
                '0'             => 'Two-row',
                'hyperactivity_0' => 68,
                'fsiq_0'        => 105,
                'total_score_0' => 17,
                'allow_partial' => true,
            ];

            $res = $engine->generate( $payload, 10, '192.0.2.55' );

            CraeAssert::same( 'success', $res['status'], $slug . ' status' );
            CraeAssert::true( array_key_exists( 'narrative_html', $res ), $slug . ' has narrative_html' );
            CraeAssert::notContains( '<script>', (string) $res['narrative_html'], $slug . ' XSS script stripped' );
            CraeAssert::notContains( 'onerror=', (string) $res['narrative_html'], $slug . ' XSS event handler stripped' );

            $ms = (float) ( $res['execution_time_ms'] ?? 0 );
            if ( $ms > $maxLatency ) {
                $maxLatency = $ms;
            }
            CraeAssert::true( $ms < 100.0, sprintf( '%s latency %.2fms < 100ms', $slug, $ms ) );
        }

        // Verify HIPAA/FERPA non-PII guarantee on audit logs (201 records, 0 examinee names or raw IPs).
        CraeAssert::same( 201, count( $wpdb->auditLogs ), 'Audit log recorded all 201 runs' );
        $serializedLogs = json_encode( $wpdb->auditLogs );
        CraeAssert::notContains( 'Taylor', (string) $serializedLogs, 'No student first name stored in audit logs' );
        CraeAssert::notContains( '192.0.2.55', (string) $serializedLogs, 'No raw client IP stored in audit logs' );
    }
}

