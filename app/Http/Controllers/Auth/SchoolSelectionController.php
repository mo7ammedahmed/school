<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Domain\Schools\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolSelectionController extends Controller
{
    public function index()
    {
        return $this->create();
    }

    public function create()
    {
        $user = Auth::user();
        $schools = $user->memberships()
            ->where('is_active', true)
            ->with('school')
            ->get()
            ->pluck('school')
            ->filter()
            ->map(fn (School $school): array => $school->only(['id', 'name', 'name_en', 'name_ar']))
            ->values();

        return inertia('auth/select-school', [
            'schools' => $schools,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
        ]);

        $user = Auth::user();
        $membership = $user->memberships()
            ->where('school_id', $request->school_id)
            ->where('is_active', true)
            ->firstOrFail();

        $membership->update([
            'last_login_at' => now(),
        ]);

        session(['school_id' => $membership->school_id]);

        // The session now speaks for a different school; a new id means a
        // token captured before the switch cannot be replayed against the one
        // it now stands for.
        $request->session()->regenerate();

        $destination = $user->hasRole('student') ? 'student.dashboard'
            : ($user->hasRole('guardian') ? 'guardian.dashboard' : 'dashboard');

        return redirect()->route($destination);
    }

    public function select(Request $request)
    {
        return $this->store($request);
    }
}
