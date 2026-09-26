<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Laravel\Head\Enums\OgType;
use App\Domain\Schools\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user has a valid, membership-backed school context.
 *
 * The session school_id is never trusted alone: it must correspond to an
 * active membership (or super-admin platform access) for the current user.
 */
class EnsureSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $schoolId = session('school_id');

        // Auto-select when the user belongs to exactly one active school,
        // so they land directly in the app instead of a school picker.
        if ($schoolId === null) {
            $activeMemberships = $user->memberships()
                ->where('is_active', true)
                ->get(['school_id']);

            if ($activeMemberships->count() === 1) {
                $schoolId = (int) $activeMemberships->first()->school_id;
                session(['school_id' => $schoolId]);
            }
        }

        $hasMembership = $schoolId !== null && $user->memberships()
            ->where('school_id', (int) $schoolId)
            ->where('is_active', true)
            ->exists();

        $isPlatformAdmin = $user->hasRole('super_admin');

        if (! $hasMembership && ! $isPlatformAdmin) {
            return redirect()->route('school.select');
        }

        $school = $schoolId !== null ? School::find((int) $schoolId) : null;

        if ($schoolId !== null && ! $school) {
            session()->forget('school_id');

            return redirect()->route('school.select');
        }

        // Expose the resolved school to controllers so they never have to
        // re-derive tenant scope from the session themselves.
        if ($school) {
            $request->attributes->set('school', $school);

            // Set Laravel Head values dynamically based on school data
            Head::title($headline = $school->name)
                ->description($school->description_en ?: 'Empowering education with Islamic values and academic excellence.')
                ->canonical()
                ->og(
                    siteName: $headline,
                    type: OgType::Website,
                    title: $headline,
                    description: $school->description_en ?: 'Empowering education with Islamic values and academic excellence.'
                )
                ->searchableByRobots()
                ->preconnect('https://fonts.bunny.net')
                ->preconnect('https://fonts.gstatic.com');
        }

        return $next($request);
    }
}
