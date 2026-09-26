<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Domain\Localization\Services\TranslationService;
use App\Domain\Schools\Models\School;
use Illuminate\Validation\ValidationException;

/**
 * Makes bilingual fields usable: an operator only has to supply one language,
 * and the other is translated and stored for them.
 *
 * Validation rules keep at least one side required so a record can never end up
 * nameless, and {@see translateBilingual()} never overwrites what was typed.
 */
trait HandlesBilingualInput
{
    /**
     * Rules for an `{base}_en` / `{base}_ar` pair where either side satisfies
     * the requirement. Merge the caller's own rules over the top (unique
     * constraints, and so on).
     *
     * @return array<string, array<int, mixed>>
     */
    protected function bilingualRules(string $base = 'name', int $max = 255): array
    {
        return [
            $base.'_ar' => ['nullable', 'string', 'max:'.$max, 'required_without:'.$base.'_en'],
            $base.'_en' => ['nullable', 'string', 'max:'.$max, 'required_without:'.$base.'_ar'],
        ];
    }

    /**
     * Fill in whichever language the operator left blank.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $bases
     * @return array<string, mixed>
     */
    protected function translateBilingual(
        array $attributes,
        array $bases = ['name'],
        School|int|null $school = null,
    ): array {
        $attributes = app(TranslationService::class)->fillMissingTranslations(
            $attributes,
            $bases,
            $school ?? (int) session('school_id'),
        );

        $this->assertBilingualFilled($attributes, $bases);

        return $attributes;
    }

    /**
     * Fill a single Arabic column from an English source, for tables that only
     * carry one translated column.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function translateInto(
        array $attributes,
        string $sourceKey,
        string $targetKey,
        School|int|null $school = null,
    ): array {
        return app(TranslationService::class)->fillInto(
            $attributes,
            $sourceKey,
            $targetKey,
            $school ?? (int) session('school_id'),
        );
    }

    /**
     * Translation is best-effort, so guard the "user typed nothing at all" case
     * with a real validation error rather than a silent empty record.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $bases
     */
    protected function assertBilingualFilled(array $attributes, array $bases = ['name']): void
    {
        foreach ($bases as $base) {
            $english = trim((string) ($attributes[$base.'_en'] ?? ''));
            $arabic = trim((string) ($attributes[$base.'_ar'] ?? ''));

            if ($english === '' && $arabic === '') {
                throw ValidationException::withMessages([
                    $base.'_en' => 'Enter a value in English or Arabic. Automatic translation can fill the other language for you.',
                ]);
            }
        }
    }
}
