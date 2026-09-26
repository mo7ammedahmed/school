<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Schools\Models\School;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Owner of the light/dark colour palettes shown in Settings → Theme.
 *
 * The palettes are stored on the school (so a whole school shares one brand)
 * while the *active* mode is a per-user preference, which is why the toggle
 * endpoint lives outside the admin-only route group.
 */
class ThemeSettingsController extends Controller
{
    public function index(): Response
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        return Inertia::render('settings/theme', [
            'themeModes' => $school->getThemeModes(),
            'mode' => $this->currentMode(),
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        $validated = $request->validate([
            'light' => 'required|array',
            'dark' => 'required|array',
            'light.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'light.background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'light.surface' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'light.text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'light.muted' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark.background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark.surface' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark.text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark.muted' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $school->setThemeModes([
            'light' => $validated['light'],
            'dark' => $validated['dark'],
        ]);

        Cache::forget("school-appearance:{$school->id}");

        return back()->with('success', 'Theme colours saved.');
    }

    /**
     * Persist the signed-in user's light/dark/system preference.
     */
    public function storeMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:light,dark,system',
        ]);

        $request->user()->update(['theme' => $validated['mode']]);

        return back();
    }

    private function currentMode(): string
    {
        return auth()->user()->theme ?? 'system';
    }

    private function activeSchool(): ?School
    {
        $schoolId = session('school_id');

        if (! $schoolId) {
            return null;
        }

        $user = auth()->user();

        if (! $user->hasRole('super_admin') && ! $user->memberships()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->exists()) {
            return null;
        }

        return School::find($schoolId);
    }
}
