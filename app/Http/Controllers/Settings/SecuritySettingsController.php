<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULTS = [
        'password_min_length' => 8,
        'password_expiry_days' => 90,
        'password_require_uppercase' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => true,
        'session_timeout' => 60,
        'max_login_attempts' => 5,
        'allowed_ips' => '',
    ];

    public function index(): Response
    {
        return inertia('settings/security', [
            'settings' => $this->settings('security', self::DEFAULTS)->all(),
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_min_length' => 'required|integer|min:6|max:128',
            'password_expiry_days' => 'required|integer|min:0|max:730',
            'password_require_uppercase' => 'sometimes|boolean',
            'password_require_numbers' => 'sometimes|boolean',
            'password_require_symbols' => 'sometimes|boolean',
            'session_timeout' => 'required|integer|min:5|max:1440',
            'max_login_attempts' => 'required|integer|min:1|max:20',
            'allowed_ips' => 'nullable|string|max:1000',
        ]);

        // Checkboxes are absent when unchecked, so read them explicitly.
        $this->settings('security', self::DEFAULTS)->save([
            'password_min_length' => $validated['password_min_length'],
            'password_expiry_days' => $validated['password_expiry_days'],
            'password_require_uppercase' => $request->boolean('password_require_uppercase'),
            'password_require_numbers' => $request->boolean('password_require_numbers'),
            'password_require_symbols' => $request->boolean('password_require_symbols'),
            'session_timeout' => $validated['session_timeout'],
            'max_login_attempts' => $validated['max_login_attempts'],
            'allowed_ips' => (string) ($validated['allowed_ips'] ?? ''),
        ]);

        return redirect()->route('settings.security')->with('success', 'Security settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
