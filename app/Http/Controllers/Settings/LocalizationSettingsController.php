<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class LocalizationSettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/localization/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'default_language' => 'required|string|max:10',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|in:12,24',
            'timezone' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
        ]);

        // TODO: Save localization settings to database or config

        return redirect()->route('settings.localization.edit')->with('success', 'Localization settings updated successfully.');
    }
}
