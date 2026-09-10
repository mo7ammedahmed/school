<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/security/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password_min_length' => 'required|integer|min:6|max:128',
            'password_require_uppercase' => 'required|boolean',
            'password_require_lowercase' => 'required|boolean',
            'password_require_numbers' => 'required|boolean',
            'password_require_symbols' => 'required|boolean',
            'session_timeout_minutes' => 'required|integer|min:5',
            'max_login_attempts' => 'required|integer|min:1',
            'lockout_duration_minutes' => 'required|integer|min:1',
            'two_factor_enabled' => 'required|boolean',
        ]);

        // TODO: Save security settings to database or config

        return redirect()->route('settings.security.edit')->with('success', 'Security settings updated successfully.');
    }
}
