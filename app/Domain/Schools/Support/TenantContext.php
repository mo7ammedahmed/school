<?php

declare(strict_types=1);

namespace App\Domain\Schools\Support;

use App\Domain\Schools\Models\School;
use Closure;
use Illuminate\Http\Request;

/**
 * The tenant the current unit of work is about.
 *
 * Tenant scope used to be re-derived at every call site — from the session in
 * most controllers, from `SchoolResolver` on the public site, and from nowhere
 * at all in console commands and queued jobs. Three different answers to "which
 * school?" is how a screen ends up with no `school_id` in its query.
 *
 * The context is explicit when somebody sets it: `EnsureSchoolContext` does that
 * for every authenticated request once the membership has been proven. When it
 * has not been set, the request's resolved school attribute and then the
 * session are consulted, so controllers and route-model binding during a normal
 * request see the same value. Outside a request — a command, a queue worker — a
 * null context means *no school*: school-owned rows are not visible, and a
 * binding resolves to 404. Falling back to "every school" would be the exact
 * bug this class exists to prevent.
 */
final class TenantContext
{
    private ?int $schoolId = null;

    private bool $explicit = false;

    /**
     * Pin the context, including to null ("no school") when that is the answer.
     */
    public function set(?int $schoolId): void
    {
        $this->schoolId = $schoolId;
        $this->explicit = true;
    }

    /**
     * Drop the pin and fall back to request-derived resolution.
     */
    public function forget(): void
    {
        $this->schoolId = null;
        $this->explicit = false;
    }

    public function id(): ?int
    {
        if ($this->explicit) {
            return $this->schoolId;
        }

        $request = app()->bound('request') ? app('request') : null;

        if (! $request instanceof Request) {
            return null;
        }

        $school = $request->attributes->get('school');

        if ($school instanceof School) {
            return (int) $school->getKey();
        }

        if (! $request->hasSession()) {
            return null;
        }

        $schoolId = (int) $request->session()->get('school_id');

        return $schoolId > 0 ? $schoolId : null;
    }

    public function hasId(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Pin the context for one unit of work, then restore what was there.
     *
     * For code that is *given* a school — an action constructed with one, a
     * service method that takes an id, a queued job carrying one — this is the
     * explicit statement the console and queue side of the mandate asks for.
     * It is also what a unit test uses instead of a session: the school is a
     * parameter, so it is pinned around the work rather than read from
     * somewhere else.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $work
     * @return TResult
     */
    public function runFor(?int $schoolId, Closure $work): mixed
    {
        $previous = $this->schoolId;
        $wasExplicit = $this->explicit;

        $this->set($schoolId);

        try {
            return $work();
        } finally {
            if ($wasExplicit) {
                $this->set($previous);
            } else {
                $this->forget();
            }
        }
    }
}
