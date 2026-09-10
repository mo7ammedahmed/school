<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PreferenceController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/preferences/edit', [
            'preferences' => [
                'locale' => auth()->user()->locale ?? config('app.locale'),
                'timezone' => auth()->user()->timezone ?? config('app.timezone'),
                'theme' => auth()->user()->theme ?? 'light',
                'notifications' => [
                    'email' => auth()->user()->email_notifications ?? true,
                    'push' => auth()->user()->push_notifications ?? true,
                    'sms' => auth()->user()->sms_notifications ?? false,
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|size:2',
            'timezone' => 'required|string',
            'theme' => 'required|in:light,dark,system',
            'notifications' => 'required|array',
            'notifications.email' => 'required|boolean',
            'notifications.push' => 'required|boolean',
            'notifications.sms' => 'required|boolean',
        ]);

        auth()->user()->update([
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'theme' => $validated['theme'],
            'email_notifications' => $validated['notifications']['email'],
            'push_notifications' => $validated['notifications']['push'],
            'sms_notifications' => $validated['notifications']['sms'],
        ]);

        return redirect()->route('settings.preferences.edit')->with('success', 'Preferences updated successfully.');
    }
}
