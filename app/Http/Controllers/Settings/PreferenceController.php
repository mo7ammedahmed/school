<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PreferenceController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        return inertia('settings/preferences', [
            'preferences' => [
                'locale' => $user->locale ?? config('app.locale'),
                'timezone' => $user->timezone ?? config('app.timezone'),
                'theme' => $user->theme ?? 'light',
                'email_notifications' => (bool) ($user->email_notifications ?? true),
                'sms_notifications' => (bool) ($user->sms_notifications ?? false),
            ],
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => 'required|in:en,ar',
            'timezone' => 'required|string|max:64',
            'theme' => 'required|in:light,dark,system',
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
        ]);

        $request->user()->update([
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'theme' => $validated['theme'],
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
        ]);

        return redirect()->route('settings.preferences')->with('success', 'Preferences updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
