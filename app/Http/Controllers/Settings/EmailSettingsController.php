<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class EmailSettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULTS = [
        'mail_driver' => 'smtp',
        'mail_host' => '',
        'mail_port' => 587,
        'mail_username' => '',
        'mail_encryption' => 'tls',
        'mail_from_address' => '',
        'mail_from_name' => '',
    ];

    public function index(): Response
    {
        $settings = $this->settings('email', self::DEFAULTS, ['mail_password'])->masked();

        return inertia('settings/email', [
            'settings' => $settings,
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => 'required|string|max:255',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'required|in:tls,ssl',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'clear_mail_password' => 'nullable|boolean',
        ]);

        $this->settings('email', self::DEFAULTS, ['mail_password'])->save($validated);

        return redirect()->route('settings.email')->with('success', 'Email settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
