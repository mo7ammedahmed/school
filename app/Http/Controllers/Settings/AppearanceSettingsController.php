<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Services\ColorService;
use App\Domain\Schools\Models\School;
use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\Activity;

class AppearanceSettingsController extends Controller
{
    public function index(): Response
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        return Inertia::render('settings/appearance', [
            'appearance' => [
                'theme' => auth()->user()->theme ?? 'system',
                'primary_color' => $school->primary_color,
                'secondary_color' => $school->secondary_color,
                'logo_path' => $school->logo_path,
                'favicon_path' => $school->favicon_path,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
            'primary_color' => [
                'required',
                'regex:/^#[0-9a-fA-F]{6}$/',
                function ($attribute, $value, $fail) {
                    /** Validate that primary color has sufficient contrast with its foreground */
                    $foreground = ColorService::getSemanticForeground($value);
                    if (!ColorService::meetsContrastStandard($foreground, $value)) {
                        $fail('The primary color does not have sufficient contrast with its text color. Please choose a different color.');
                    }
                }
            ],
            'secondary_color' => [
                'required',
                'regex:/^#[0-9a-fA-F]{6}$/',
                function ($attribute, $value, $fail) {
                    /** Validate that secondary color has sufficient contrast with its foreground */
                    $foreground = ColorService::getSemanticForeground($value);
                    if (!ColorService::meetsContrastStandard($foreground, $value)) {
                        $fail('The secondary color does not have sufficient contrast with its text color. Please choose a different color.');
                    }
                }
            ],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico,webp|max:512',
        ]);

        $oldLogo = $school->logo_path;
        $oldFavicon = $school->favicon_path;
        $oldAppearance = $school->only([
            'primary_color',
            'secondary_color',
            'logo_path',
            'favicon_path',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('school-branding', 'public');
        }

        if ($request->hasFile('favicon')) {
            $validated['favicon_path'] = $request->file('favicon')->store('school-branding', 'public');
        }

        // Remove validation-only fields
        unset($validated['theme'], $validated['logo'], $validated['favicon']);

        // Update school with appearance settings
        $school->update($validated);

        // Update user's theme preference
        auth()->user()->update(['theme' => $request->input('theme')]);

        // Clean up old files if new ones were uploaded
        if ($oldLogo && isset($validated['logo_path'])) {
            Storage::disk('public')->delete($oldLogo);
        }

        if ($oldFavicon && isset($validated['favicon_path'])) {
            Storage::disk('public')->delete($oldFavicon);
        }

        // Log the appearance changes
        $newAppearance = $school->only(array_keys($oldAppearance));
        $changedKeys = collect($newAppearance)
            ->keys()
            ->filter(fn (string $key): bool => $oldAppearance[$key] !== $newAppearance[$key])
            ->values()
            ->all();

        Activity::causedBy(auth()->user())
            ->performedOn($school)
            ->withProperties([
                'school_id' => $school->id,
                'changed_keys' => $changedKeys,
                'old' => array_intersect_key($oldAppearance, array_flip($changedKeys)),
                'new' => array_intersect_key($newAppearance, array_flip($changedKeys)),
                'theme' => [
                    'old' => $request->user()->getOriginal('theme'),
                    'new' => $request->user()->theme,
                ],
            ])
            ->log('appearance_updated');

        // Clear appearance cache for this school
        Cache::forget("school-appearance:{$school->id}");

        return redirect()->route('settings.appearance')->with('success', 'Appearance settings updated successfully.');
    }

    private function activeSchool(): ?School
    {
        $schoolId = session('school_id');

        if (!$schoolId) {
            return null;
        }

        $user = auth()->user();

        if (!$user->hasRole('super_admin') && !$user->memberships()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->exists()) {
            return null;
        }

        return School::find($schoolId);
    }
}
