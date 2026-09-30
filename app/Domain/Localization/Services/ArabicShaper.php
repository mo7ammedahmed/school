<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

/**
 * Arabic text shaping for renderers that cannot join letters.
 *
 * dompdf draws one character per glyph in logical order: it has no Arabic
 * joining and no bidirectional reordering, so an Arabic sentence comes out as a
 * row of disconnected, mirrored letters — what the school reported as
 * "the Arabic is not readable". The bundled DejaVu Sans font does carry the
 * Arabic Presentation Forms-B block (U+FE70–FEFC), so the fix belongs in PHP:
 * pick the correct positional form for every letter, then hand the renderer the
 * visual (already reversed) order it expects.
 *
 * Usage from Blade: the `shaped` directive (`$subject->name`) shapes only when
 * the locale is Arabic, while `ArabicShaper::shape($text)` always shapes.
 *
 * Each line is shaped separately so a newline can never travel to another
 * position while the line is being reversed.
 */
final class ArabicShaper
{
    /**
     * Positional forms per Arabic letter: [isolated, final, initial, medial].
     *
     * A null marks a form the letter does not have, which is also what tells
     * the shaper whether the letter can join on that side: no initial form means
     * it cannot join to the next letter (ALEF, DAL, REH, WAW, TEH MARBUTA …),
     * no final form means it cannot join to the previous one (HAMZA).
     *
     * Only the standard U+0621–U+064A letters are listed, because the glyphs
     * they map to (U+FE80–FEF4) are the ones the bundled font is known to
     * contain. Persian/Urdu extensions (PEH, TCHEH, KEHEH …) live in the
     * Presentation Forms-A block, which the font does not ship, so those letters
     * stay unshaped — still legible, simply not joined.
     */
    private const FORMS = [
        0x0621 => [0xFE80, null, null, null],           // HAMZA
        0x0622 => [0xFE81, 0xFE82, null, null],         // ALEF WITH MADDA
        0x0623 => [0xFE83, 0xFE84, null, null],         // ALEF WITH HAMZA ABOVE
        0x0624 => [0xFE85, 0xFE86, null, null],         // WAW WITH HAMZA
        0x0625 => [0xFE87, 0xFE88, null, null],         // ALEF WITH HAMZA BELOW
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],     // YEH WITH HAMZA
        0x0627 => [0xFE8D, 0xFE8E, null, null],         // ALEF
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],     // BEH
        0x0629 => [0xFE93, 0xFE94, null, null],         // TEH MARBUTA
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],     // TEH
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],     // THEH
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],     // JEEM
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],     // HAH
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],     // KHAH
        0x062F => [0xFEA9, 0xFEAA, null, null],         // DAL
        0x0630 => [0xFEAB, 0xFEAC, null, null],         // THAL
        0x0631 => [0xFEAD, 0xFEAE, null, null],         // REH
        0x0632 => [0xFEAF, 0xFEB0, null, null],         // ZAIN
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],     // SEEN
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],     // SHEEN
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],     // SAD
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],     // DAD
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],     // TAH
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],     // ZAH
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],     // AIN
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],     // GHAIN
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],     // FEH
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],     // QAF
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],     // KAF
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],     // LAM
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],     // MEEM
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],     // NOON
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],     // HEH
        0x0648 => [0xFEED, 0xFEEE, null, null],         // WAW
        0x0649 => [0xFEEF, 0xFEF0, null, null],         // ALEF MAKSURA
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],     // YEH
    ];

    /**
     * LAM followed by one of these letters becomes a single ligature glyph:
     * alef code point => [isolated, final].
     */
    private const LAM_ALEF = [
        0x0622 => [0xFEF5, 0xFEF6],                     // ALEF WITH MADDA
        0x0623 => [0xFEF7, 0xFEF8],                     // ALEF WITH HAMZA ABOVE
        0x0625 => [0xFEF9, 0xFEFA],                     // ALEF WITH HAMZA BELOW
        0x0627 => [0xFEFB, 0xFEFC],                     // ALEF
    ];

    /**
     * Vowel marks and other combining marks. They carry no joining behaviour, so
     * they are both ignored when looking for a letter's neighbours and dropped
     * from the output: an unvocalised sheet reads normally, a mangled one does
     * not.
     */
    private static function isMark(int $point): bool
    {
        return ($point >= 0x064B && $point <= 0x065F)
            || $point === 0x0670
            || ($point >= 0x06D6 && $point <= 0x06ED);
    }

    /** Brackets mirror when a phrase is drawn right to left. */
    private const MIRRORS = [
        '(' => ')', ')' => '(',
        '[' => ']', ']' => '[',
        '{' => '}', '}' => '{',
        '«' => '»', '»' => '«',
        '<' => '>', '>' => '<',
    ];

    /**
     * Shaped text for a string that is about to be printed: Arabic when the app
     * is running in Arabic, untouched otherwise.
     */
    public static function forLocale(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return self::active() ? self::shape($text) : $text;
    }

    /** Whether the current locale renders right to left. */
    public static function active(): bool
    {
        return app()->getLocale() === 'ar';
    }

    /**
     * Joins the letters of every Arabic word and returns the string in visual
     * order, ready for a left-to-right renderer.
     */
    public static function shape(string $text): string
    {
        if ($text === '' || preg_match('/[\x{0600}-\x{06FF}\x{FB50}-\x{FEFF}]/u', $text) !== 1) {
            // Nothing Arabic in here, so there is nothing to shape or reorder.
            return $text;
        }

        $lines = preg_split('/(\r\n|\r|\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        return implode('', array_map(
            fn (string $part): string => in_array($part, ["\r\n", "\r", "\n"], true)
                ? $part
                : self::reorder(self::join($part)),
            $lines
        ));
    }

    /**
     * Picks a positional form for every letter of the string (still in logical
     * order).
     */
    private static function join(string $text): string
    {
        $points = self::codePoints($text);
        $total = count($points);
        $output = '';
        $consumed = [];

        for ($index = 0; $index < $total; $index++) {
            $point = $points[$index];

            if (isset($consumed[$index]) || self::isMark($point)) {
                continue;
            }

            $forms = self::FORMS[$point] ?? null;

            if ($forms === null) {
                $output .= self::character($point);

                continue;
            }

            $previousIndex = self::previousLetter($points, $index);
            $nextIndex = self::nextLetter($points, $index);

            $joinsBefore = $previousIndex !== null && self::joinsAfter($points[$previousIndex]);
            $joinsAfter = $nextIndex !== null && self::joinsBefore($points[$nextIndex]);

            // LAM + ALEF is drawn as one glyph, so the alef is consumed here and
            // skipped when the loop reaches it.
            if ($point === 0x0644 && $nextIndex !== null && isset(self::LAM_ALEF[$points[$nextIndex]])) {
                $ligature = self::LAM_ALEF[$points[$nextIndex]];
                $output .= self::character($joinsBefore ? $ligature[1] : $ligature[0]);
                $consumed[$nextIndex] = true;

                continue;
            }

            [, $final, $initial, $medial] = $forms;

            $output .= self::character(match (true) {
                $joinsBefore && $joinsAfter && $medial !== null => $medial,
                $joinsBefore && $final !== null => $final,
                $joinsAfter && $initial !== null => $initial,
                default => $forms[0],
            });
        }

        return $output;
    }

    /** True when the letter can connect to the letter that follows it. */
    private static function joinsAfter(int $point): bool
    {
        return (self::FORMS[$point][2] ?? null) !== null;
    }

    /** True when the letter can connect to the letter that precedes it. */
    private static function joinsBefore(int $point): bool
    {
        return (self::FORMS[$point][1] ?? null) !== null;
    }

    /** Index of the nearest preceding letter, ignoring marks. */
    private static function previousLetter(array $points, int $index): ?int
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            if (! self::isMark($points[$cursor])) {
                return $cursor;
            }
        }

        return null;
    }

    /** Index of the nearest following letter, ignoring marks. */
    private static function nextLetter(array $points, int $index): ?int
    {
        $total = count($points);

        for ($cursor = $index + 1; $cursor < $total; $cursor++) {
            if (! self::isMark($points[$cursor])) {
                return $cursor;
            }
        }

        return null;
    }

    /**
     * Untangles the bidirectional runs: right-to-left words are reversed and
     * mirrored, left-to-right runs (dates, times, Latin names) keep their order,
     * and the runs themselves swap places.
     */
    private static function reorder(string $text): string
    {
        $points = self::codePoints($text);
        $runs = [];
        $pending = [];

        foreach ($points as $point) {
            $direction = self::direction($point);

            if ($direction === null) {
                // Punctuation and spaces stay with the run they follow, so they
                // travel to the correct end when that run is reversed.
                if ($runs === []) {
                    $pending[] = $point;
                } else {
                    $runs[count($runs) - 1]['points'][] = $point;
                }

                continue;
            }

            $last = count($runs) - 1;

            if ($last >= 0 && $runs[$last]['rtl'] === $direction) {
                $runs[$last]['points'][] = $point;

                continue;
            }

            $runs[] = ['rtl' => $direction, 'points' => [...$pending, $point]];
            $pending = [];
        }

        if ($runs === []) {
            return $text;
        }

        if ($pending !== []) {
            $runs[count($runs) - 1]['points'] = [...$runs[count($runs) - 1]['points'], ...$pending];
        }

        $visual = '';

        foreach (array_reverse($runs) as $run) {
            $runPoints = $run['rtl'] ? array_reverse($run['points']) : $run['points'];

            foreach ($runPoints as $point) {
                $char = self::character($point);
                $visual .= $run['rtl'] ? (self::MIRRORS[$char] ?? $char) : $char;
            }
        }

        return $visual;
    }

    /**
     * The direction a code point belongs to, or null when it is neutral.
     *
     * @return bool|null True for right-to-left, false for left-to-right.
     */
    private static function direction(int $point): ?bool
    {
        // Latin letters, European and both Arabic-Indic digit sets read left to
        // right, even inside an Arabic sentence.
        if (($point >= 0x0030 && $point <= 0x0039)
            || ($point >= 0x0041 && $point <= 0x005A)
            || ($point >= 0x0061 && $point <= 0x007A)
            || ($point >= 0x00C0 && $point <= 0x024F)
            || ($point >= 0x0660 && $point <= 0x0669)
            || ($point >= 0x06F0 && $point <= 0x06F9)) {
            return false;
        }

        if (($point >= 0x0600 && $point <= 0x06FF)
            || ($point >= 0x0750 && $point <= 0x077F)
            || ($point >= 0x08A0 && $point <= 0x08FF)
            || ($point >= 0xFB50 && $point <= 0xFDFF)
            || ($point >= 0xFE70 && $point <= 0xFEFC)) {
            return true;
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private static function codePoints(string $text): array
    {
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(self::ordinal(...), $characters);
    }

    /** UTF-8 code point of a single character, without relying on mbstring. */
    private static function ordinal(string $char): int
    {
        $bytes = array_values(unpack('C*', $char) ?: []);
        $length = count($bytes);

        return match ($length) {
            1 => $bytes[0],
            2 => (($bytes[0] & 0x1F) << 6) | ($bytes[1] & 0x3F),
            3 => (($bytes[0] & 0x0F) << 12) | (($bytes[1] & 0x3F) << 6) | ($bytes[2] & 0x3F),
            4 => (($bytes[0] & 0x07) << 18) | (($bytes[1] & 0x3F) << 12) | (($bytes[2] & 0x3F) << 6) | ($bytes[3] & 0x3F),
            default => 0xFFFD,
        };
    }

    /** UTF-8 encoding of a code point. */
    private static function character(int $point): string
    {
        if ($point < 0x80) {
            return chr($point);
        }

        if ($point < 0x800) {
            return chr(0xC0 | ($point >> 6)).chr(0x80 | ($point & 0x3F));
        }

        if ($point < 0x10000) {
            return chr(0xE0 | ($point >> 12))
                .chr(0x80 | (($point >> 6) & 0x3F))
                .chr(0x80 | ($point & 0x3F));
        }

        return chr(0xF0 | ($point >> 18))
            .chr(0x80 | (($point >> 12) & 0x3F))
            .chr(0x80 | (($point >> 6) & 0x3F))
            .chr(0x80 | ($point & 0x3F));
    }
}
