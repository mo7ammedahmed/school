<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
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
     * others. There is one copy now, and a request with no school context is
     * always refused rather than quietly asking the database about school zero.
     *
     * `school.context` is what puts the value in the session; anything reaching
     * a controller without it is a routing mistake, not an empty list.
     */
    protected function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        return $schoolId;
    }

    /**
     * Refuses a record that belongs to another school.
     *
     * Tenant scoping on the query only covers reads; every route that takes a
     * model off the URL still has to prove the record is the operator's, or one
     * school can open another's invoice by guessing an id.
     */
    protected function ensureOwned(Model $model, int $status = 403): void
    {
        abort_unless((int) $model->getAttribute('school_id') === $this->schoolId(), $status);
    }
}
