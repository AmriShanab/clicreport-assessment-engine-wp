<?php
/**
 * Multi-Rater / Informant Management Service
 *
 * @package ClicReport\AssessmentEngine\Services\Narrative
 */

namespace ClicReport\AssessmentEngine\Services\Narrative;

defined( 'ABSPATH' ) || exit;

final class InformantManager {

    /**
     * Parse and sanitize up to 5 raters from incoming assessment payload.
     */
    public static function extractInformants( array $data ): array {
        $informants       = [];
        $defaultNames     = [ 'the rater one', 'the rater two', 'the rater three', 'the rater four', 'the rater five' ];
        $defaultRelations = [ 'mother', 'general education teacher', 'special education teacher', 'caregiver', 'clinician' ];

        for ( $i = 0; $i < 5; $i++ ) {
            $nameKey  = match ( $i ) {
                0       => 'informant_one',
                1       => 'informant_two',
                2       => 'informant_three',
                default => 'informant_' . ( $i + 1 ),
            };
            $relKey   = $nameKey . '_relationship';
            $titleKey = 'informer_title' . $i;

            $rawName = $data[ $nameKey ] ?? ( $data[ $titleKey ] ?? ( $data[ 'rater_' . ( $i + 1 ) . '_name' ] ?? '' ) );
            $rawRel  = $data[ $relKey ] ?? ( $data[ 'rater_' . ( $i + 1 ) . '_role' ] ?? ( $defaultRelations[ $i ] ?? '' ) );

            $name     = sanitize_text_field( trim( (string) $rawName ) );
            $relation = sanitize_text_field( trim( (string) $rawRel ) );

            if ( '' === $name || '0' === $name ) {
                $name = $defaultNames[ $i ];
            }

            $relLower  = strtolower( $relation );
            $isTeacher = str_contains( $relLower, 'teacher' ) || str_contains( $relLower, 'educator' ) || str_contains( $relLower, 'instructor' );
            $isSelf    = str_contains( $relLower, 'self' ) || str_contains( $relLower, 'student' ) || str_contains( $relLower, 'examinee' );

            $informants[ $i ] = [
                'index'      => $i,
                'name'       => $name,
                'relation'   => $relation,
                'is_teacher' => $isTeacher,
                'is_self'    => $isSelf,
                'is_parent'  => ! $isTeacher && ! $isSelf && ( '' !== $relLower ),
            ];
        }

        return $informants;
    }

    public static function resolveActiveRaterCount( array $data ): int {
        if ( ! empty( $data['active_raters'] ) && is_numeric( $data['active_raters'] ) ) {
            return max( 1, min( 5, (int) $data['active_raters'] ) );
        }
        if ( ! empty( $data['3'] ) || ! empty( $data['rater_checkbox_3'] ) || ! empty( $data['informant_five'] ) || ! empty( $data['informant_5'] ) ) { return 5; }
        if ( ! empty( $data['2'] ) || ! empty( $data['rater_checkbox_2'] ) || ! empty( $data['informant_four'] ) || ! empty( $data['informant_4'] ) ) { return 4; }
        if ( ! empty( $data['1'] ) || ! empty( $data['rater_checkbox_1'] ) || ! empty( $data['informant_three'] ) || ! empty( $data['informant_3'] ) ) { return 3; }
        if ( ! empty( $data['0'] ) || ! empty( $data['rater_checkbox_0'] ) || ! empty( $data['informant_two'] ) || ! empty( $data['informant_2'] ) ) { return 2; }
        return 1;
    }

    public static function buildRatersIntro( array $informants, int $activeRatersCount, string $possessivePronoun ): string {
        $intro = "{$possessivePronoun} {$informants[0]['relation']}";
        if ( $activeRatersCount >= 3 ) {
            $intro .= " ({$informants[0]['name']}), {$informants[1]['relation']} ({$informants[1]['name']}), and {$informants[2]['relation']} ({$informants[2]['name']}).";
        } elseif ( 2 === $activeRatersCount ) {
            $intro .= ", {$informants[0]['name']} and {$informants[1]['relation']}, {$informants[1]['name']}.";
        } else {
            $intro .= ", {$informants[0]['name']}.";
        }
        return esc_html( $intro );
    }

    public static function buildMultiRaterAgreement( array $scores, array $informants, int $raterCount ): string {
        $r1 = $scores[1]['range'] ?? 'N/A';
        $r2 = $scores[2]['range'] ?? 'N/A';
        $r3 = $scores[3]['range'] ?? 'N/A';

        if ( $raterCount >= 3 && 'N/A' !== $r2 && 'N/A' !== $r3 ) {
            if ( $r1 === $r2 && $r1 === $r3 ) {
                return 'by all evaluators on this measure.';
            }
            return esc_html( "by {$informants[0]['name']}, within the {$r2} range by {$informants[1]['name']}, and within the {$r3} range by {$informants[2]['name']}." );
        }

        if ( $raterCount >= 2 && 'N/A' !== $r2 ) {
            if ( $r1 === $r2 ) {
                return esc_html( "by both {$informants[0]['name']} and {$informants[1]['name']}." );
            }
            return esc_html( "by {$informants[0]['name']} but within the {$r2} range by {$informants[1]['name']}." );
        }

        return esc_html( "on this measure by {$informants[0]['name']}." );
    }

    public static function buildTeacherRestrictedParagraph(
        array $scale,
        array $scores,
        array $informants,
        int $raterCount,
        string $possName
    ): string {
        $pClass = esc_attr( "{$scale['scale_key']}_p p_0 Social-body_n pre" );
        $tag    = ! empty( $scale['is_composite'] ) ? 'b' : 'em';
        $title  = esc_html( (string) $scale['title'] );
        $desc   = wp_kses_post( (string) $scale['description'] );

        $html      = "<p class='{$pClass}'><{$tag}>{$title}:</{$tag}> {$desc} ";
        $r1Teacher = $informants[0]['is_teacher'];
        $r2Teacher = ( $raterCount >= 2 && $informants[1]['is_teacher'] );
        $r3Teacher = ( $raterCount >= 3 && $informants[2]['is_teacher'] );

        $r1Range = esc_html( (string) ( $scores[1]['range'] ?? 'N/A' ) );
        $r2Range = esc_html( (string) ( $scores[2]['range'] ?? 'N/A' ) );
        $r3Range = esc_html( (string) ( $scores[3]['range'] ?? 'N/A' ) );

        $possEsc = esc_html( $possName );
        $r1Rel   = esc_html( $informants[0]['relation'] );
        $r2Rel   = esc_html( $informants[1]['relation'] );
        $r2Name  = esc_html( $informants[1]['name'] );
        $r3Name  = esc_html( $informants[2]['name'] );

        if ( $r1Teacher && $r2Teacher ) {
            $html .= " {$possEsc} {$r1Rel} reported concerns within the {$r1Range} range for overall {$title} and {$r2Name} reported {$r2Range} functioning.";
        } elseif ( $r1Teacher ) {
            $html .= " {$possEsc} {$r1Rel} reported concerns within the {$r1Range} range for overall {$title}.";
        } elseif ( $r2Teacher ) {
            $html .= " An overall composite for {$title} is only obtained when the assessment is completed by a teacher/educator. {$possEsc} {$r2Rel} reported concerns within the {$r2Range} range for overall {$title}.";
        } else {
            $html .= " An overall composite for {$title} is only obtained when the assessment is completed by a teacher/educator.";
        }

        if ( $r3Teacher ) {
            $html .= " Additionally, {$r3Name} reported concerns within the {$r3Range} range on this composite.";
        }

        $html .= '</p>';
        return $html;
    }
}

