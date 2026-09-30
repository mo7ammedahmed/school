<?php

declare(strict_types=1);

namespace App\Domain\Schools\Support;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant scoping for a school-owned model.
 *
 * Every school-owned table keys off `school_id`, but until now each caller wrote
 * the `where` itself — which is how four public pages ended up with no `where`
 * at all. Giving the filter a name makes the omission visible at the call site:
 * a query with no `forSchool()` reads as unfinished.
 *
 * A null id deliberately matches nothing. "No school could be resolved" must
 * never degrade into "every school's rows".
 */
trait BelongsToSchool
{
    #[Scope]
    protected function forSchool(Builder $query, ?int $schoolId): Builder
    {
        return $query->where($this->qualifyColumn('school_id'), $schoolId);
    }
}
