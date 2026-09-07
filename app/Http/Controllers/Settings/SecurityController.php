<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        return inertia('settings/security/two-factor', [
            'twoFactorEnabled' => $user->two_factor_enabled ?? false,
            'twoFactorSecret' => $user->two_factor_secret,
            'recoveryCodes' => $user->two_factor_recovery_codes,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($user->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid two-factor authentication code.']);
        }

        $user->forceFill([
            'two_factor_enabled' => true,
        ])->save();

        Cache::forget('two-factor-codes:' . $user->id);

        return redirect()->route('settings.security.two-factor')->with('success', 'Two-factor authentication enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('settings.security.two-factor')->with('success', 'Two-factor authentication disabled.');
    }
}
