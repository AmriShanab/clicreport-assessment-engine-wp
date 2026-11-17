<?php
/**
 * Clinical Token Interpolation & wp_kses() Output Sanitizer
 *
 * @package ClicReport\AssessmentEngine\Services\Narrative
 */

namespace ClicReport\AssessmentEngine\Services\Narrative;

defined( 'ABSPATH' ) || exit;

final class NarrativeInterpolator {

    /**
     * Interpolate tokens ({examinee_name}, {possessive}) and conditional blocks ({?flag:...}).
     */
    public static function render( ?string $template, array $context = [] ): string {
        if ( null === $template || '' === $template ) {
            return '';
        }

        $output = $template;

        if ( str_contains( $output, '{?' ) ) {
            $output = preg_replace_callback(
                '/\{\?([a-zA-Z0-9_]+):(.*?)\}/s',
                static fn( array $m ): string => ! empty( $context[ $m[1] ] ) ? $m[2] : '',
                $output
            ) ?? $output;
        }

        $replacements = [];
        foreach ( $context as $key => $value ) {
            if ( is_scalar( $value ) || null === $value ) {
                $replacements[ '{' . $key . '}' ] = esc_html( (string) $value );
            }
        }

        if ( isset( $context['examinee_name'] ) && ! isset( $replacements['{name}'] ) ) {
            $replacements['{name}'] = esc_html( (string) $context['examinee_name'] );
        }

        $output = strtr( $output, $replacements );
        $output = preg_replace( '/ {2,}/', ' ', $output ) ?? $output;

        return trim( $output );
    }

    /**
     * Build sanitized examinee grammar context.
     */
    public static function buildExamineeContext( ?string $rawName, ?string $rawGender ): array {
        $name           = ReportGrammar::formatName( $rawName );
        $possessiveName = ReportGrammar::formatPossessive( $name );
        $pronouns       = ReportGrammar::resolvePronouns( $rawGender );

        return [
            'examinee_name'   => $name,
            'name'            => $name,
            'possessive_name' => $possessiveName,
            'possessive'      => $pronouns['possessive'],
            'Possessive'      => $pronouns['Possessive'],
            'subject'         => $pronouns['subject'],
            'Subject'         => $pronouns['Subject'],
            'object'          => $pronouns['object'],
            'Object'          => $pronouns['Object'],
            'reflexive'       => $pronouns['reflexive'],
            'to_be_present'   => $pronouns['to_be_present'],
            'to_be_past'      => $pronouns['to_be_past'],
            'have_present'    => $pronouns['have_present'],
            'gender'          => $pronouns['gender'],
        ];
    }

    /**
     * Filter clinical HTML report output through WordPress wp_kses() allowlist.
     */
    public static function sanitizeClinicalHtml( string $html ): string {
        $allowedTags = [
            'div'    => [ 'class' => true ],
            'p'      => [ 'class' => true, 'style' => true ],
            'span'   => [ 'class' => true, 'style' => true ],
            'b'      => [],
            'strong' => [],
            'em'     => [],
            'i'      => [],
            'u'      => [],
            'br'     => [],
            'h2'     => [ 'class' => true ],
            'h3'     => [ 'class' => true ],
            'h4'     => [ 'class' => true ],
            'h5'     => [ 'class' => true ],
            'ul'     => [ 'class' => true ],
            'ol'     => [ 'class' => true ],
            'li'     => [ 'class' => true ],
            'table'  => [ 'class' => true, 'style' => true ],
            'thead'  => [ 'class' => true ],
            'tbody'  => [ 'class' => true ],
            'tr'     => [ 'class' => true, 'style' => true ],
            'th'     => [ 'class' => true, 'style' => true, 'colspan' => true, 'rowspan' => true ],
            'td'     => [ 'class' => true, 'style' => true, 'colspan' => true, 'rowspan' => true ],
        ];

        return wp_kses( $html, $allowedTags );
    }
}

