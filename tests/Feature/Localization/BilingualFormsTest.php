<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The server accepts whichever language the operator typed and fills the other
 * one, so the forms must not contradict it: an English-only `required` field
 * blocks an Arabic-only entry before the request is ever made, and a pair with
 * no translate control leaves the second language unreachable.
 */
class BilingualFormsTest extends TestCase
{
    /**
     * The one form where English really is mandatory: SchoolController builds
     * the slug from `name_en`, so the server rejects an Arabic-only school.
     */
    private const array ENGLISH_REQUIRED_PAGES = [
        'js/Pages/schools/form.tsx',
    ];

    public function test_bilingual_name_fields_do_not_require_english(): void
    {
        $offenders = [];

        foreach ($this->pageFiles() as $path => $lines) {
            if (in_array($path, self::ENGLISH_REQUIRED_PAGES, true)) {
                continue;
            }

            foreach ($lines as $index => $line) {
                if (! preg_match('/name="[a-z_]+_en"/', $line)) {
                    continue;
                }

                // The attributes are often spread over several lines, so the
                // whole element is examined — and only that element, or the
                // next field's `required` would be blamed on this one.
                if (preg_match('/\brequired\b/', $this->element($lines, $index))) {
                    $offenders[] = $path.':'.($index + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These English fields are required, so an Arabic-only entry cannot be saved: '
                .implode(', ', $offenders),
        );
    }

    public function test_every_bilingual_field_has_a_translate_control(): void
    {
        $offenders = [];

        foreach ($this->pageFiles() as $path => $lines) {
            $contents = implode("\n", $lines);

            if (! preg_match('/name="[a-z_]+_ar"/', $contents)) {
                continue;
            }

            if (! str_contains($contents, 'TranslatePair')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These pages accept Arabic but offer no way to translate the other side: '
                .implode(', ', $offenders),
        );
    }

    /**
     * The JSX element starting at (or before) the given line, as one string.
     *
     * @param  list<string>  $lines
     */
    private function element(array $lines, int $index): string
    {
        $start = $index;

        while ($start > 0 && ! preg_match('/<(Input|Textarea|input|textarea)\b/', $lines[$start])) {
            $start--;
        }

        $end = $index;

        while ($end < count($lines) - 1 && ! str_contains($lines[$end], '/>')) {
            $end++;
        }

        return implode(' ', array_slice($lines, $start, $end - $start + 1));
    }

    /**
     * Every page component, keyed by its path relative to the app's Pages
     * directory, with its lines.
     *
     * @return array<string, list<string>>
     */
    private function pageFiles(): array
    {
        $files = [];

        foreach (File::allFiles(resource_path('js/Pages')) as $file) {
            if (! str_ends_with($file->getFilename(), '.tsx')) {
                continue;
            }

            $files[str_replace(resource_path().DIRECTORY_SEPARATOR, '', $file->getPathname())] =
                file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [];
        }

        return $files;
    }
}
