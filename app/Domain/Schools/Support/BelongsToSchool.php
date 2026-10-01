<?php

declare(strict_types=1);

namespace App\Domain\Schools\Support;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

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
 *
 * A model that uses this trait is scoped by {@see TenantScope} on every query,
 * and stamps its own `school_id` on create. Both failure modes are closed: an
 * unpinned context reads nothing and cannot write. `withoutSchoolScope()` is
 * the escape hatch for the few places that mean to cross schools — migrations,
 * platform reporting, the public site resolving its own school.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            // An explicit value wins — server-side code that resolved a school
            // for a public page or a seeder may name it — but a write that
            // names no school and has no context has no tenant to belong to,
            // and is refused rather than filed under null.
            $schoolId = $model->getAttribute('school_id')
                ?? app(TenantContext::class)->id();

            if ($schoolId === null) {
                throw new LogicException(sprintf(
                    '%s cannot be created without a tenant context: set one with TenantContext::set(), or pass school_id explicitly.',
                    $model::class,
                ));
            }

            $model->setAttribute('school_id', (int) $schoolId);
        });

        static::updating(function (Model $model): void {
            // A row belongs to one school for its whole life. Reassigning it
            // would move every query that already knows its id into a tenant
            // that never created it.
            if ($model->isDirty('school_id')) {
                throw new LogicException(sprintf('%s cannot be moved between schools.', $model::class));
            }
        });
    }

    /**
     * Run a query across every school.
     *
     * Named so the crossing is greppable: each call site is a deliberate
     * "this is not tenant work" statement, not a missing filter.
     *
     * @return Builder<static>
     */
    public static function withoutSchoolScope(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }

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
