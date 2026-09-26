<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULTS = [
        'email_enrollment' => true,
        'email_attendance' => true,
        'email_exam' => true,
        'email_assignment' => false,
        'email_announcement' => true,
    ];

    public function index(): Response
    {
        return inertia('settings/notifications-config', [
            'settings' => $this->settings('notifications', self::DEFAULTS)->all(),
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email_enrollment' => 'sometimes|boolean',
            'email_attendance' => 'sometimes|boolean',
            'email_exam' => 'sometimes|boolean',
            'email_assignment' => 'sometimes|boolean',
            'email_announcement' => 'sometimes|boolean',
        ]);

        // Checkboxes are absent when unchecked, so read them explicitly rather
        // than trusting the payload to carry every key.
        $this->settings('notifications', self::DEFAULTS)->save([
            'email_enrollment' => $request->boolean('email_enrollment'),
            'email_attendance' => $request->boolean('email_attendance'),
            'email_exam' => $request->boolean('email_exam'),
            'email_assignment' => $request->boolean('email_assignment'),
            'email_announcement' => $request->boolean('email_announcement'),
        ]);

        return redirect()->route('settings.notifications-config')->with('success', 'Notification settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
