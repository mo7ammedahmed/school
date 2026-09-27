<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

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
 * operator typed.
 */
final readonly class BilingualBackfill
{
    /** Stop the run after this many failures so a broken key cannot hammer the provider. */
    private const int MAX_FAILURES = 3;

    /** Rows read per query so a large table does not load at once. */
    private const int CHUNK = 200;

    public function __construct(private TranslationService $translations) {}

    /**
     * @return array{
     *     totals: array{scanned: int, missing: int, translated: int, failed: int, remaining: int},
     *     targets: list<array{label: string, scanned: int, missing: int, translated: int, failed: int}>,
     *     error: string|null
     * }
     */
    public function run(int $schoolId): array
    {
        $budget = max(0, (int) config('bilingual.max_translations_per_run', 100));
        $stop = false;
        $error = null;
        $targets = [];

        /** @var list<array<string, mixed>> $configured */
        $configured = config('bilingual.targets', []);

        foreach ($configured as $target) {
            $targets[] = $this->fillTarget($target, $schoolId, $budget, $stop, $error);

            if ($stop) {
                break;
            }
        }

        $totals = [
            'scanned' => array_sum(array_column($targets, 'scanned')),
            'missing' => array_sum(array_column($targets, 'missing')),
            'translated' => array_sum(array_column($targets, 'translated')),
            'failed' => array_sum(array_column($targets, 'failed')),
            'remaining' => 0,
        ];

        $totals['remaining'] = max(0, $totals['missing'] - $totals['translated'] - $totals['failed']);

        return ['totals' => $totals, 'targets' => $targets, 'error' => $error];
    }

    /**
     * @param  array<string, mixed>  $target
     * @return array{label: string, scanned: int, missing: int, translated: int, failed: int}
     */
    private function fillTarget(array $target, int $schoolId, int &$budget, bool &$stop, ?string &$error): array
    {
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
            &$report, &$budget, &$stop, &$error, $pairs, $schoolId,
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

                    if ($budget <= 0) {
                        continue;
                    }

                    $emptyColumn = $english !== null ? $pair['ar'] : $pair['en'];

                    $translated = $english !== null
                        ? $this->translations->translateQuietly($english, 'en', 'ar', $schoolId)
                        : $this->translations->translateQuietly((string) $arabic, 'ar', 'en', $schoolId);

                    if ($translated === null) {
                        $report['failed']++;
                        $error ??= 'The translation service did not answer for at least one value. Check the key, model and provider above.';

                        if ($report['failed'] >= self::MAX_FAILURES) {
                            $stop = true;

                            return false;
                        }

                        continue;
                    }

                    $record->forceFill([$emptyColumn => $translated])->save();

                    $budget--;
                    $report['translated']++;
                }
            }

            return true;
        });

        return $report;
    }

    /**
     * Narrows the query to one school. Most tables carry `school_id`, but the
     * school row itself is scoped by its own id, an organisation is scoped
     * through the school that owns it, and child rows are scoped through their
     * parent relation.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $target
     * @return bool false when there is nothing to scope to and the target must be skipped
     */
    private function scopeQuery(Builder $query, array $target, int $schoolId): bool
    {
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
