<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    /**
     * The historic landing page, forwarded to the school form.
     *
     * A controller method rather than the closure this used to be, and the
     * reason is a deployment one: `route:cache` refuses to serialise a closure,
     * so a single `fn () => redirect()` in `routes/web.php` made
     * `php artisan optimize` fail — the command Laravel Cloud runs as part of
     * the build. The two screens cannot drift apart while one of them is a
     * redirect.
     */
    public function general(): RedirectResponse
    {
        return redirect()->route('settings.school');
    }

    public function index(): Response
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school !== null, 404);

        return inertia('settings/school/edit', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'name_en' => $school->name_en,
                'name_ar' => $school->name_ar,
                'description' => $school->description_en, // Default to English for now, could be localized
                'description_en' => $school->description_en,
                'description_ar' => $school->description_ar,
                'email' => $school->email,
                'phone' => $school->phone,
                'address' => $school->address,
                'logo_path' => $school->logo_path,
                'primary_color' => $school->primary_color,
                'secondary_color' => $school->secondary_color,
                'accent_color' => $school->accent_color,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school !== null, 404);

        $validated = $request->validate([
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        // The upload key is dropped so it is never written to a column that
        // does not exist; the empty language side is filled by the observer.

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('school-logos', 'public');
        }

        unset($validated['logo']);

        $school->update($validated);

        return redirect()->route('settings.school')->with('success', 'School settings updated successfully.');
    }
}
