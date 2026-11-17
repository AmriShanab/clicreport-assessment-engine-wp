<?php
/**
 * Unit Tests: Pronoun Resolution, Grammar Conjugation & Informant Engine
 *
 * @package ClicReport\AssessmentEngine\Tests\Unit
 */

declare(strict_types=1);

use ClicReport\AssessmentEngine\Services\Narrative\InformantManager;
use ClicReport\AssessmentEngine\Services\Narrative\ReportGrammar;

final class ReportGrammarTest {

    public function run(): void {
        // 1. Pronoun resolution & verb conjugation
        $she = ReportGrammar::resolvePronouns( 'she/her' );
        CraeAssert::same( 'she', $she['subject'], 'she/her subject' );
        CraeAssert::same( 'is', $she['to_be_present'], 'she/her to_be_present' );
        CraeAssert::same( 'herself', $she['reflexive'], 'she/her reflexive' );

        $they = ReportGrammar::resolvePronouns( 'they/them' );
        CraeAssert::same( 'they', $they['subject'], 'they/them subject' );
        CraeAssert::same( 'are', $they['to_be_present'], 'they/them plural verb' );
        CraeAssert::same( 'were', $they['to_be_past'], 'they/them plural past verb' );

        $he = ReportGrammar::resolvePronouns( 'unknown_value' );
        CraeAssert::same( 'he', $he['subject'], 'default fallback pronoun' );

        // 2. Name sanitization, capitalization & possessive rules
        CraeAssert::same( 'Alex', ReportGrammar::formatName( '<script>alert(1)</script>alex' ), 'XSS stripped from name' );
        CraeAssert::same( 'the examinee', ReportGrammar::formatName( '' ), 'empty name fallback' );
        CraeAssert::same( "Alex's", ReportGrammar::formatPossessive( 'alex' ), 'singular possessive' );
        CraeAssert::same( "Lucas'", ReportGrammar::formatPossessive( 'lucas' ), 'trailing s possessive' );
        CraeAssert::same( "the examinee's", ReportGrammar::formatPossessive( '' ), 'default possessive' );

        // 3. Oxford comma list formatting
        CraeAssert::same( '', ReportGrammar::joinList( [] ), 'empty list' );
        CraeAssert::same( 'Hyperactivity', ReportGrammar::joinList( [ 'Hyperactivity' ] ), '1-item list' );
        CraeAssert::same( 'Hyperactivity and Aggression', ReportGrammar::joinList( [ 'Hyperactivity', 'Aggression' ] ), '2-item list' );
        CraeAssert::same( 'Hyperactivity, Aggression, and Anxiety', ReportGrammar::joinList( [ 'Hyperactivity', 'Aggression', 'Anxiety' ] ), '3-item Oxford list' );

        // 4. Informant extraction & teacher/parent classification
        $informants = InformantManager::extractInformants(
            [
                'informant_one'              => 'Ms. Rivera',
                'informant_one_relationship' => 'General Education Teacher',
                'informant_two'              => 'Taylor',
                'informant_two_relationship' => 'Mother',
            ]
        );
        CraeAssert::true( $informants[0]['is_teacher'], 'Rater 1 detected as teacher' );
        CraeAssert::true( $informants[1]['is_parent'], 'Rater 2 detected as parent' );
        CraeAssert::same( 2, InformantManager::resolveActiveRaterCount( [ '0' => 'Two-row' ] ), 'Active rater count=2' );
    }
}

