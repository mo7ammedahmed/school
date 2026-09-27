<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\HandlesBilingualInput;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    use HandlesBilingualInput;

    public function index(): Response
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school, 404);

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

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school, 404);

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

        // Fill whichever language was left blank, then drop the upload key so it
        // is never written to a column that does not exist.
        $validated = $this->translateBilingual($validated, ['name'], $school->id);
        // The description is optional: translate it when one side was typed,
        // but never refuse the save because both sides are empty.
        $validated = $this->translateBilingual($validated, ['description'], $school->id, required: false);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('school-logos', 'public');
        }

        unset($validated['logo']);

        $school->update($validated);

        return redirect()->route('settings.school')->with('success', 'School settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
