<?php
/**
 * ClicReport Assessment Engine — Test Runner (Phase 6)
 *
 * Execute from CLI:
 *   php clicreport-assessment-engine/tests/run.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Unit/AssessmentScorerTest.php';
require_once __DIR__ . '/Unit/ReportGrammarTest.php';
require_once __DIR__ . '/Unit/ArchetypeProcessorsTest.php';
require_once __DIR__ . '/Integration/TemplateCatalogRegressionTest.php';
require_once __DIR__ . '/Integration/RestApiSecurityTest.php';

$suites = [
    AssessmentScorerTest::class,
    ReportGrammarTest::class,
    ArchetypeProcessorsTest::class,
    TemplateCatalogRegressionTest::class,
    RestApiSecurityTest::class,
];

$passed = 0;
$failed = 0;

foreach ( $suites as $suiteClass ) {
    $before = CraeAssert::$assertions;
    try {
        ( new $suiteClass() )->run();
        $count = CraeAssert::$assertions - $before;
        echo sprintf( "[PASS] %-34s (%d assertions)\n", $suiteClass, $count );
        $passed++;
    } catch ( \Throwable $e ) {
        $failed++;
        echo sprintf( "[FAIL] %-34s %s\n", $suiteClass, $e->getMessage() );
    }
}

echo str_repeat( '-', 60 ) . "\n";
echo sprintf(
    "Test Suites: %d passed, %d failed | Total Assertions: %d\n",
    $passed,
    $failed,
    CraeAssert::$assertions
);

exit( $failed > 0 ? 1 : 0 );

