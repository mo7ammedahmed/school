<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Schools\Services\SchoolResolver;
use App\Domain\Schools\Support\BelongsToSchool;

/**
 * The one owner of "which school is this public page about?".
 *
 * A visitor has no school in their session, so the public site shows the site's
 * own school and every public query has to be scoped to it. Each controller
 * used to decide that for itself, and the ones that forgot served every school's
 * news and events to everybody — including, through `/news/{post}`, any other
 * school's article.
 *
 * Every public controller extends this and asks it; nothing here re-reads the
 * session directly.
 */
abstract class PublicController
{
    public function __construct(protected readonly SchoolResolver $schools) {}

    /**
     * The school this page belongs to, or null when none can be resolved.
     *
     * A null id must scope a query to *nothing*. Passing it straight into
     * `where('school_id', null)` happens to fail closed, but it fails silently;
     * {@see BelongsToSchool::forSchool()} is the
     * supported way to apply this value.
     */
    protected function schoolId(): ?int
    {
        return $this->schools->current()?->id;
    }
}
