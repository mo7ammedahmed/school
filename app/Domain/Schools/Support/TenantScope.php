<?php

declare(strict_types=1);

namespace App\Domain\Schools\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters every query on a school-owned model by the active tenant.
 *
 * `forSchool()` made an omission visible at the call site, but visible is not
 * enforced: a query written without it still returned every school's rows, and
 * the screens that were audited by hand were exactly the ones somebody
 * remembered to look at. A global scope is the enforcement — a model that
 * carries {@see BelongsToSchool} cannot be queried across schools by accident.
 *
 * The failure mode is deliberately closed: with no tenant context the
 * comparison is against null, which matches nothing. A console command or a
 * queued job that means to work on a school must pin the context first; one
 * that forgets does not read every school's data, it reads none of it.
 *
 * Cross-school reads remain possible where the application genuinely means them
 * — platform reporting, migrations, the public site resolving its school — and
 * those call sites say so with `withoutSchoolScope()`.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('school_id'), app(TenantContext::class)->id());
    }
}
