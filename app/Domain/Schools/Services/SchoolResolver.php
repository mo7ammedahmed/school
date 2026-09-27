<?php

declare(strict_types=1);

namespace App\Domain\Schools\Services;

use App\Domain\Schools\Models\School;
use Illuminate\Support\Facades\Cache;

/**
 * The one place that answers "which school is this request about?".
 *
 * The session value is a *request* concern, so it is passed in rather than read
 * here. When nobody has a school selected — a guest on the marketing site — the
 * first school is used so its branding and metadata still apply.
 */
final class SchoolResolver
{
    /** Cached id of the fallback tenant. Only the id is cached: a serialised model comes back incomplete. */
    public const DEFAULT_CACHE_KEY = 'school.default';

    public const DEFAULT_TTL_MINUTES = 5;

    public function current(?int $sessionSchoolId = null, bool $allowFallback = true): ?School
    {
        $schoolId = $sessionSchoolId ?? (int) session('school_id');

        if ($schoolId > 0) {
            $school = School::find($schoolId);

            if ($school instanceof School) {
                return $school;
            }
        }

        if (! $allowFallback) {
            return null;
        }

        $defaultId = Cache::remember(
            self::DEFAULT_CACHE_KEY,
            now()->addMinutes(self::DEFAULT_TTL_MINUTES),
            static fn () => School::query()->orderBy('id')->value('id'),
        );

        return $defaultId ? School::find((int) $defaultId) : null;
    }
}
