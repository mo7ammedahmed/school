<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Schools\Support\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The school this request is working in.
     *
     * Every tenant table keys off this value, and eighteen controllers used to
     * carry their own private copy of the same three lines — which meant a
     * missing context was a silent `school_id = 0` in some pages and a 403 in
     * others. There is one answer now, `TenantContext`, pinned by
     * `school.context` after membership is proven and read here; a request that
     * arrives without it is a routing mistake, and is refused rather than
     * quietly asking the database about school zero.
     *
     * Controllers do not have to prove ownership of a bound model any more:
     * `BelongsToSchool` resolves route bindings inside this context, so a
     * foreign id never reaches an action.
     */
    protected function schoolId(): int
    {
        $schoolId = app(TenantContext::class)->id();

        if ($schoolId === null) {
            abort(403, 'No school context is available for this request.');
        }

        return $schoolId;
    }
}
