<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(): Response
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school, 404);

        return inertia('settings/school/edit', [
            'school' => $school,
        ]);
    }

    public function index(): Response
    {
        return $this->edit();
    }

    public function update(Request $request): RedirectResponse
    {
        $school = auth()->user()->currentSchool;
        abort_unless($school, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('school-logos', 'public');
        }

        $school->update($validated);

        return redirect()->route('settings.school')->with('success', 'School settings updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->update($request);
    }
}
