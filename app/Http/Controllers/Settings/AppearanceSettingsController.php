<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class AppearanceSettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/appearance/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
            'primary_color' => 'required|string|max:20',
            'sidebar_color' => 'required|string|max:20',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('settings', 'public');
        } else {
            unset($validated['logo']);
        }

        // TODO: Save appearance settings to database or config

        return redirect()->route('settings.appearance.edit')->with('success', 'Appearance settings updated successfully.');
    }
}
