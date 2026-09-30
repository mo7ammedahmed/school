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

    /**
     * Resolve a route binding inside the active tenant.
     *
     * Implicit binding is how a foreign id was reachable at all: the router
     * resolved `{enrollment}` straight from the primary key, so `show`, `edit`,
     * `update` and `destroy` had nothing to check and one school could open
     * another's record by guessing an id. Adding the tenant to the binding query
     * turns that into a 404 before the action runs — the same answer a missing
     * row gets, because to this school the row is missing.
     *
     * It fails closed: with no context the comparison is against null, which
     * matches nothing. A route that carries a school-owned binding must have run
     * `school.context` first, and this is what makes forgetting it loud.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->where($this->qualifyColumn('school_id'), app(TenantContext::class)->id());
    }
}
