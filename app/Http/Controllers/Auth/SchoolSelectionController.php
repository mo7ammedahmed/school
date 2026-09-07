<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolSelectionController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $schools = $user->memberships()
            ->where('is_active', true)
            ->with('school')
            ->get()
            ->pluck('school');

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

        session(['school_id' => $request->school_id]);

        return redirect()->route('dashboard');
    }
}
