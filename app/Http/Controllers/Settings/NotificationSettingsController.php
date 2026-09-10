<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/notifications/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'email_notifications' => 'required|boolean',
            'sms_notifications' => 'required|boolean',
            'push_notifications' => 'required|boolean',
            'attendance_alerts' => 'required|boolean',
            'grade_alerts' => 'required|boolean',
            'announcement_alerts' => 'required|boolean',
        ]);

        // TODO: Save notification settings to database or config

        return redirect()->route('settings.notifications.edit')->with('success', 'Notification settings updated successfully.');
    }
}
