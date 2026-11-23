<?php

/**
 * Clinical Linguistic & Pronoun Grammar Engine
 *
 * @package ClicReport\AssessmentEngine\Services\Narrative
 */

namespace ClicReport\AssessmentEngine\Services\Narrative;

defined('ABSPATH') || exit;

final class ReportGrammar
{

    /**
     * Resolve grammatical pronoun tokens based on examinee gender/pronoun preference.
     */
    public static function resolvePronouns(?string $gender): array
    {
        $gender = strtolower(trim((string) $gender));

        return match ($gender) {
            'female', 'f', 'girl', 'woman', 'she/her' => [
                'gender'          => 'female',
                'subject'         => 'she',
                'Subject'         => 'She',
                'object'          => 'her',
                'Object'          => 'Her',
                'possessive'      => 'her',
                'Possessive'      => 'Her',
                'possessive_pron' => 'hers',
                'reflexive'       => 'herself',
                'to_be_present'   => 'is',
                'to_be_past'      => 'was',
                'have_present'    => 'has',
            ],
            'neutral', 'non-binary', 'they', 'other', 'they/them' => [
                'gender'          => 'neutral',
                'subject'         => 'they',
                'Subject'         => 'They',
                'object'          => 'them',
                'Object'          => 'Them',
                'possessive'      => 'their',
                'Possessive'      => 'Their',
                'possessive_pron' => 'theirs',
                'reflexive'       => 'themselves',
                'to_be_present'   => 'are',
                'to_be_past'      => 'were',
                'have_present'    => 'have',
            ],
            default => [
                'gender'          => 'male',
                'subject'         => 'he',
                'Subject'         => 'He',
                'object'          => 'him',
                'Object'          => 'Him',
                'possessive'      => 'his',
                'Possessive'      => 'His',
                'possessive_pron' => 'his',
                'reflexive'       => 'himself',
                'to_be_present'   => 'is',
                'to_be_past'      => 'was',
                'have_present'    => 'has',
            ],
        };
    }

    public static function formatName(?string $name): string
    {
        $clean = sanitize_text_field(trim((string) $name));
        return '' === $clean ? 'the examinee' : ucwords($clean);
    }

    public static function formatPossessive(?string $name): string
    {
        $name = self::formatName($name);
        if ('the examinee' === $name) {
            return "the examinee's";
        }
        if (str_ends_with($name, "'s") || str_ends_with($name, "’s")) {
            return $name;
        }
        return (str_ends_with($name, 's') || str_ends_with($name, 'S'))
            ? $name . "'"
            : $name . "'s";
    }

    public static function joinList(array $items, string $conjunction = 'and'): string
    {
        $filtered = array_values(array_filter(array_map('trim', $items), static fn($v) => '' !== $v));
        $count    = count($filtered);

        if (0 === $count) {
            return '';
        }
        if (1 === $count) {
            return $filtered[0];
        }
        if (2 === $count) {
            return "{$filtered[0]} {$conjunction} {$filtered[1]}";
        }

        $last = array_pop($filtered);
        return implode(', ', $filtered) . ", {$conjunction} {$last}";
    }

    public static function indefiniteArticle(string $word): string
    {
        $trimmed = ltrim($word);
        if ('' === $trimmed) {
            return 'a';
        }
        return in_array(strtolower($trimmed[0]), ['a', 'e', 'i', 'o', 'u'], true) ? 'an' : 'a';
    }

    public static function formatOrdinal(int|float|string $number): string
    {
        $str = trim((string) $number);
        if ('' === $str || 'N/A' === $str) {
            return 'N/A';
        }
        if (! is_numeric(str_replace(['<', '>'], '', $str))) {
            return $str;
        }
        if (str_contains($str, '.')) {
            return $str . 'th';
        }

        $intVal = (int) preg_replace('/[^0-9]/', '', $str);
        $mod100 = $intVal % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $str . 'th';
        }

        return match ($intVal % 10) {
            1       => $str . 'st',
            2       => $str . 'nd',
            3       => $str . 'rd',
            default => $str . 'th',
        };
    }

    /**
     * Clean and polish clinical narrative grammar, spacing, and punctuation.
     * 
     * @param string $text Raw Generated HTML or plain text narrative
     * @return string Polished Narrative
     */

    public static function cleanNarrative(string $text): string
    {
        if ('' === trim($text)) {
            return '';
        }

        $text = preg_replace('/\s+([,.;:?!])/', '$1', $text);
        $text = preg_replace('/([,;])\s*[,;]+/', '$1', $text);
        $text = preg_replace('/\.\s*\.+/', '.', $text);

        $text = preg_replace('/\b(and|or)\s*[,;]/i', '$1', $text);
        $text = preg_replace('/,\s*(and|or)\s*\./i', '.', $text);

        $text = preg_replace('/\b(\w+)\s+\1\b/i', '$1', $text);
        $text = preg_replace('/\b(a)\s+([aeiouAEIOU]\w*)/', 'an $2', $text);
        $text = preg_replace('/\b(a)\s+(8\d?th|11th|18th)\b/i', 'an $2', $text);
        $text = preg_replace('/\b(an)\s+([bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]\w*)/', 'a $2', $text);
        $text = preg_replace('/([.?!])([A-Z])/', '$1 $2', $text);
        $text = preg_replace_callback(
            '/(^|[.!?]\s+|<p>\s*)([a-z])/',
            static fn(array $m): string => $m[1] . strtoupper($m[2]),
            $text
        );

        return trim($text);
    }
}
