<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Localization;

use App\Domain\Localization\Services\ArabicShaper;
use PHPUnit\Framework\TestCase;

/**
 * The shaper exists because dompdf draws Arabic one unjoined, unmirrored letter
 * at a time. Each expectation below is the glyph sequence a correct Arabic
 * renderer would produce, written out as presentation forms so a regression
 * cannot hide behind "it looks Arabic enough".
 */
class ArabicShaperTest extends TestCase
{
    public function test_it_joins_a_word_and_reverses_it_into_visual_order(): void
    {
        // سلام → seen(initial) + lam-alef(final ligature) + meem(isolated),
        // drawn left to right as meem, ligature, seen.
        $this->assertSame(
            "\u{FEE1}\u{FEFC}\u{FEB3}",
            ArabicShaper::shape('سلام')
        );
    }

    public function test_it_picks_the_right_form_for_every_position(): void
    {
        // مدرسة: meem initial, dal final, reh isolated (it never joins what
        // follows it), seen initial, teh marbuta final — reversed for the
        // renderer.
        $this->assertSame(
            "\u{FE94}\u{FEB3}\u{FEAD}\u{FEAA}\u{FEE3}",
            ArabicShaper::shape('مدرسة')
        );
    }

    public function test_it_builds_the_lam_alef_ligature(): void
    {
        $this->assertSame("\u{FEFB}", ArabicShaper::shape('لا'));

        // الأحد: alef isolated, lam-alef-hamza isolated ligature, hah initial,
        // dal final.
        $this->assertSame(
            "\u{FEAA}\u{FEA3}\u{FEF7}\u{FE8D}",
            ArabicShaper::shape('الأحد')
        );
    }

    public function test_it_drops_vowel_marks_instead_of_breaking_joining(): void
    {
        // The damma and the final tanween are marks: they neither break the
        // joining of the letters around them nor reach the output.
        $this->assertSame(ArabicShaper::shape('مدرسة'), ArabicShaper::shape('مُدَرِّسَةٌ'));
    }

    public function test_it_leaves_latin_text_untouched(): void
    {
        $this->assertSame('Sunday 08:00', ArabicShaper::shape('Sunday 08:00'));
        $this->assertSame('Room 101', ArabicShaper::shape('Room 101'));
    }

    public function test_it_keeps_times_and_numbers_reading_left_to_right(): void
    {
        // The digits must stay "08:00" while the day name flips around them.
        $this->assertSame(
            "08:00 \u{FEAA}\u{FEA3}\u{FEF7}\u{FE8D}",
            ArabicShaper::shape('الأحد 08:00')
        );
    }

    public function test_it_never_moves_a_line_break_to_another_line(): void
    {
        $shaped = ArabicShaper::shape("الأحد\nالاثنين");

        $this->assertSame(
            "\u{FEAA}\u{FEA3}\u{FEF7}\u{FE8D}\n\u{FEE6}\u{FEF4}\u{FEE8}\u{FE9B}\u{FEFB}\u{FE8D}",
            $shaped
        );
    }

    public function test_it_mirrors_brackets_inside_a_right_to_left_phrase(): void
    {
        // The phrase flips, and each bracket keeps the shape an Arabic reader
        // expects on that side: an opening paren drawn at the left edge.
        $this->assertSame(
            "(\u{FE94}\u{FEB3}\u{FEAD}\u{FEAA}\u{FEE3})",
            ArabicShaper::shape('(مدرسة)')
        );
    }

    public function test_it_returns_empty_and_short_strings_safely(): void
    {
        $this->assertSame('', ArabicShaper::shape(''));
        $this->assertSame('—', ArabicShaper::shape('—'));
        $this->assertSame("\u{FEE1}", ArabicShaper::shape('م'));
    }
}
