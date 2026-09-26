<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Communication\Services\SmsSender;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SmsSettingsController extends Controller
{
    public function index(): Response
    {
        $sender = SmsSender::for($this->schoolId());

        return inertia('settings/sms', [
            'settings' => $sender->masked(),
            'providers' => [
                ['value' => 'log', 'label' => 'Log only (no messages are sent)'],
                ['value' => 'unifonic', 'label' => 'Unifonic'],
                ['value' => 'twilio', 'label' => 'Twilio'],
            ],
            'configured' => $sender->isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:'.implode(',', SmsSender::PROVIDERS),
            'sender_id' => 'nullable|string|max:64',
            'account_sid' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:500',
            'auth_token' => 'nullable|string|max:500',
        ]);

        SmsSender::for($this->schoolId())->save($validated);

        return back()->with('success', 'SMS settings saved.');
    }

    private function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        return $schoolId;
    }
}
