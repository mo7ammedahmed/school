<?php

declare(strict_types=1);

namespace App\Domain\Localization\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Where a translation is happening.
 *
 * Reading `session()` and `app()->runningInConsole()` inline made the on-save
 * observer impossible to exercise without a request and impossible to reuse
 * from a test, and it scattered the same question — "is this record's school
 * the one in the session?" — across every caller. Those answers live here now,
 * and the observer takes one of these instead of reaching for them itself.
 */
final class TranslationContext
{
    public function __construct(
        private readonly int $schoolId = 0,
        private readonly bool $filling = true,
    ) {}

    /**
     * The context of the request being handled right now.
     *
     * Filling is a request behaviour. A console run is a *bulk* run — an import
     * or a command — and translating a thousand rows behind the operator's back
     * is not what "translate what you type" means, so those leave the work to
     * the sweep unless `bilingual.autofill_in_console` asks for it. The test
     * suite is not a bulk run in that sense: it stands in for the browser, and
     * `tests/TestCase.php` sets exactly that flag so the ordinary request path
     * is what the suite exercises.
     */
    public static function current(): self
    {
        return new self(
            schoolId: (int) session('school_id'),
            filling: ! app()->runningInConsole() || (bool) config('bilingual.autofill_in_console', false),
        );
    }

    /** A context pinned to one school, for callers that already know it. */
    public static function forSchool(int $schoolId): self
    {
        return new self(schoolId: $schoolId);
    }

    /** A context that never fills: what an import or a command should look like. */
    public static function withoutFilling(): self
    {
        return new self(filling: false);
    }

    public function schoolId(): int
    {
        return $this->schoolId;
    }

    /**
     * The school a record belongs to, falling back to the one this request
     * belongs to. Rows without a school column (global presets) get the latter.
     */
    public function schoolIdFor(Model $model): int
    {
        $id = (int) ($model->getAttribute('school_id') ?? 0);

        return $id > 0 ? $id : $this->schoolId;
    }

    /** Whether the empty side of a pair may be filled here. */
    public function fillsOnSave(): bool
    {
        return $this->filling;
    }
}
