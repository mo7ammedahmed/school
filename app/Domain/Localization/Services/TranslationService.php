<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Exceptions\TranslationFailed;
use App\Domain\Schools\Models\School;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one place the app asks for a translation.
 *
 * Two shapes are supported: an explicit translation (the button in the UI) and
 * filling the empty side of a bilingual pair on save, which is what lets an
 * operator type English and still store Arabic.
 */
class TranslationService
{
    public function __construct(private readonly AiTranslator $translator) {}

    /**
     * Translate or fail loudly — this backs the explicit "translate" action.
     *
     * @throws TranslationFailed
     */
    public function translate(string $text, string $from, string $to, School|int|null $school = null): string
    {
        return $this->translator->translate($text, $from, $to, $this->settings($school));
    }

    /**
     * Translate or return null. Used where a failure must not break the request.
     */
    public function translateQuietly(string $text, string $from, string $to, School|int|null $school = null): ?string
    {
        try {
            $translated = $this->translate($text, $from, $to, $school);

            return $translated === '' ? null : $translated;
        } catch (Throwable $e) {
            Log::warning('Automatic translation skipped', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fill the empty side of every `{base}_en` / `{base}_ar` pair in the given
     * attributes. Values the operator typed are never overwritten.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $bases
     * @return array<string, mixed>
     */
    public function fillMissingTranslations(
        array $attributes,
        array $bases = ['name'],
        School|int|null $school = null,
    ): array {
        if (! $this->autoTranslateEnabled($school)) {
            return $attributes;
        }

        foreach ($bases as $base) {
            $attributes = $this->fillPair($attributes, $base.'_en', $base.'_ar', $school);
        }

        return $attributes;
    }

    /**
     * Fill a single target column from a source column, for tables that only
     * carry one Arabic column (content pages, for example).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function fillInto(
        array $attributes,
        string $sourceKey,
        string $targetKey,
        School|int|null $school = null,
    ): array {
        if (! $this->autoTranslateEnabled($school)) {
            return $attributes;
        }

        $source = $this->text($attributes, $sourceKey);
        $target = $this->text($attributes, $targetKey);

        if ($source === null || $target !== null) {
            return $attributes;
        }

        $translation = $this->translateQuietly(
            $source,
            $this->settings($school)->sourceLocale(),
            $this->settings($school)->targetLocale(),
            $school,
        );

        if ($translation !== null) {
            $attributes[$targetKey] = $translation;
        }

        return $attributes;
    }

    public function autoTranslateEnabled(School|int|null $school = null): bool
    {
        return $this->settings($school)->autoTranslate() && $this->settings($school)->isConfigured();
    }

    public function settings(School|int|null $school = null): TranslationSettings
    {
        return TranslationSettings::for($this->schoolId($school));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function fillPair(
        array $attributes,
        string $englishKey,
        string $arabicKey,
        School|int|null $school,
    ): array {
        $english = $this->text($attributes, $englishKey);
        $arabic = $this->text($attributes, $arabicKey);

        if ($english === null && $arabic === null) {
            return $attributes;
        }

        if ($english !== null && $arabic === null) {
            $translation = $this->translateQuietly($english, 'en', 'ar', $school);

            if ($translation !== null) {
                $attributes[$arabicKey] = $translation;
            }

            return $attributes;
        }

        // Reaching here means the Arabic side is filled and the English one is
        // the only empty side (both-empty and English-only already returned).
        if ($english === null) {
            $translation = $this->translateQuietly($arabic, 'ar', 'en', $school);

            if ($translation !== null) {
                $attributes[$englishKey] = $translation;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function text(array $attributes, string $key): ?string
    {
        $value = $attributes[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function schoolId(School|int|null $school): int
    {
        if ($school instanceof School) {
            return (int) $school->id;
        }

        if (is_int($school)) {
            return $school;
        }

        return (int) session('school_id');
    }
}
