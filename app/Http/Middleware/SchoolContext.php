<?php

namespace App\Http\Middleware;

use App\Domain\Schools\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SchoolContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): \Illuminate\Http\Response  $next
     * @return \Illuminate\Http\Response
     */
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

        if ($schoolId !== null && ! School::find((int) $schoolId)) {
            session()->forget('school_id');

            return redirect()->route('school.select');
        }

        return $next($request);
    }
}
