<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class SmsSettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/sms/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'sms_provider' => 'required|string|max:255',
            'sms_api_key' => 'nullable|string|max:255',
            'sms_api_secret' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:255',
            'sms_enabled' => 'required|boolean',
        ]);

        // TODO: Save SMS settings to database or config

        return redirect()->route('settings.sms.edit')->with('success', 'SMS settings updated successfully.');
    }
}
