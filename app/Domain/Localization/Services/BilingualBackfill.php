<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Exceptions\TranslationFailed;
use App\Domain\Localization\Observers\FillsMissingTranslations;
use App\Domain\Localization\Support\BilingualTargets;
use App\Domain\Localization\Support\TranslationBudget;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Sweeps every bilingual table for a school and fills whichever language is
 * missing, so a school that started out monolingual can be brought fully
 * bilingual without opening each record.
 *
 * Runs off the same {@see TranslationService} the on-save auto-fill uses, so it
 * obeys the school's provider, key and model, and it never overwrites a value an
 * operator typed. What it may spend — the count, the wall clock and the host's
 * own limit — comes from {@see TranslationBudget}, which the on-save fill shares:
 * one definition of "a call that still fits" for both.
 */
final readonly class BilingualBackfill
{
    /** Stop the run after this many failures so a broken key cannot hammer the provider. */
    private const int MAX_FAILURES = 3;

    /** Rows read per query so a large table does not load at once. */
    private const int CHUNK = 200;

    public function __construct(private TranslationService $translations) {}

    /**
     * @param  int|null  $limit  translations to perform before returning; null
     *                           uses the configured budget, 0 scans without
     *                           translating (used to size the backlog)
     * @param  float|null  $seconds  wall-clock budget for the whole run; null
     *                               uses the configured limit
     * @return array{
     *     totals: array{scanned: int, missing: int, translated: int, failed: int, remaining: int, en_to_ar: int, ar_to_en: int},
     *     targets: list<array{label: string, scanned: int, missing: int, translated: int, failed: int, remaining: int, en_to_ar: int, ar_to_en: int}>,
     *     error: string|null
     * }
     */
    public function run(int $schoolId, ?int $limit = null, ?float $seconds = null): array
    {
        // Two clocks and a count, from one place: the soft clock keeps each
        // request short so the screen shows progress between batches, and PHP's
        // own wall clock is what a call must still finish inside.
        $budget = TranslationBudget::forRun($seconds, $limit);

        $stop = false;
        $error = null;
        $targets = [];

        foreach (BilingualTargets::all() as $target) {
            $targets[] = $this->fillTarget($target, $schoolId, $budget, $stop, $error);

            if ($stop) {
                break;
            }
        }

        // Per-target totals were counted as the scan walked the table, so a
        // target can still read "2 missing" after the run filled both of them in
        // the same request. `remaining` is what the screen shows, so the report
        // cannot claim there is work left that the run just did.
        foreach ($targets as $index => $target) {
            $targets[$index]['remaining'] = max(0, $target['missing'] - $target['translated'] - $target['failed']);
        }

        $totals = [
            'scanned' => array_sum(array_column($targets, 'scanned')),
            'missing' => array_sum(array_column($targets, 'missing')),
            'translated' => array_sum(array_column($targets, 'translated')),
            'failed' => array_sum(array_column($targets, 'failed')),
            'remaining' => 0,
            'en_to_ar' => array_sum(array_column($targets, 'en_to_ar')),
            'ar_to_en' => array_sum(array_column($targets, 'ar_to_en')),
        ];

        $totals['remaining'] = max(0, $totals['missing'] - $totals['translated'] - $totals['failed']);

        return ['totals' => $totals, 'targets' => $targets, 'error' => $error];
    }

    /**
     * @param  array<string, mixed>  $target
     * @return array{label: string, scanned: int, missing: int, translated: int, failed: int, remaining: int, en_to_ar: int, ar_to_en: int}
     */
    private function fillTarget(
        array $target,
        int $schoolId,
        TranslationBudget $budget,
        bool &$stop,
        ?string &$error,
    ): array {
        /** @var class-string<Model> $model */
        $model = $target['model'];
        $scope = (string) ($target['scope'] ?? 'school_id');

        /** @var list<array{en: string, ar: string}> $pairs */
        $pairs = $target['pairs'];

        $report = [
            'label' => (string) $target['label'],
            'scanned' => 0,
            'missing' => 0,
            'translated' => 0,
            'failed' => 0,
            'remaining' => 0,
            // Which way each value travelled, so the screen can show that both
            // directions are covered rather than claiming "translated 12".
            'en_to_ar' => 0,
            'ar_to_en' => 0,
        ];

        $columns = ['id'];

        // A relation-scoped table has no school column of its own to select.
        if (! isset($target['scope_relation'])) {
            $columns[] = $scope;
        }

        foreach ($pairs as $pair) {
            $columns[] = $pair['en'];
            $columns[] = $pair['ar'];
        }

        $query = $model::query()->select(array_values(array_unique($columns)));

        if (! $this->scopeQuery($query, $target, $schoolId)) {
            return $report;
        }

        $query->chunkById(self::CHUNK, function (Collection $records) use (
            &$report, $budget, &$stop, &$error, $pairs, $schoolId,
        ): bool {
            foreach ($records as $record) {
                $report['scanned']++;

                foreach ($pairs as $pair) {
                    $english = $this->text($record->{$pair['en']});
                    $arabic = $this->text($record->{$pair['ar']});

                    // Nothing to translate from, or already bilingual.
                    if (($english === null && $arabic === null) || ($english !== null && $arabic !== null)) {
                        continue;
                    }

                    $report['missing']++;

                    // Out of room: the value is counted and left for the next
                    // batch, which is what makes "still empty" honest.
                    if (! $budget->canStartCall()) {
                        continue;
                    }

                    $emptyColumn = $english !== null ? $pair['ar'] : $pair['en'];
                    $direction = $english !== null ? 'en_to_ar' : 'ar_to_en';

                    try {
                        $translated = $english !== null
                            ? $this->translations->translate($english, 'en', 'ar', $schoolId)
                            : $this->translations->translate((string) $arabic, 'ar', 'en', $schoolId);
                    } catch (TranslationFailed $failure) {
                        $report['failed']++;
                        $error ??= $failure->getMessage();

                        if ($failure->statusCode === 429 || $report['failed'] >= self::MAX_FAILURES) {
                            $stop = true;

                            return false;
                        }

                        continue;
                    }

                    // The sweep translates deliberately, so the on-save fill is
                    // suppressed: otherwise every write here would try to
                    // translate its own pair again inside the run's budget.
                    FillsMissingTranslations::withoutFilling(
                        fn () => $record->forceFill([$emptyColumn => $translated])->save(),
                    );

                    // One value from the run's count; the wall clock looks after itself.
                    $budget->spend();
                    $report['translated']++;
                    $report[$direction]++;
                }
            }

            return true;
        });

        return $report;
    }

    /**
     * Narrows the query to one school. Most tables carry `school_id`, but the
     * school row itself is scoped by its own id, an organisation is scoped
     * through the school that owns it, child rows are scoped through their
     * parent relation, and a handful of tables (theme presets) are global and
     * belong to no school at all.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $target
     * @return bool false when there is nothing to scope to and the target must be skipped
     */
    private function scopeQuery(Builder $query, array $target, int $schoolId): bool
    {
        if (($target['scope'] ?? null) === 'global') {
            return true;
        }

        if ($relation = $target['scope_relation'] ?? null) {
            $query->whereHas(
                (string) $relation,
                static fn (Builder $related) => $related->where('school_id', $schoolId),
            );

            return true;
        }

        $column = (string) ($target['scope'] ?? 'school_id');

        $value = ($target['scope_value'] ?? 'school') === 'organization'
            ? School::whereKey($schoolId)->value('organization_id')
            : $schoolId;

        if ($value === null || (int) $value === 0) {
            return false;
        }

        $query->where($column, (int) $value);

        return true;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
