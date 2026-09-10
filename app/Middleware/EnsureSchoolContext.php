<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $schoolId = session('school_id');
        $membership = $user->memberships()
            ->where('is_active', true)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->first();

        if (! $membership) {
            session()->forget('school_id');
            $membership = $user->memberships()->where('is_active', true)->first();

            if (! $membership) {
                return redirect()->route('school.select')->with('error', 'No active school membership found.');
            }
        }

        session(['school_id' => $membership->school_id]);

        return $next($request);
    }
}
