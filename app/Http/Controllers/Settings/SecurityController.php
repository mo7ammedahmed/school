<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Identity\Services\RecoveryCodes;
use App\Domain\Identity\Services\TotpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Enrolment is two-step: the secret is generated and shown first, and
        // only becomes active once the user proves they can produce a code from
        // it. Reusing the stored secret keeps a page refresh from invalidating
        // a QR code the user has already scanned.
        $pendingSecret = null;

        if (! $user->two_factor_enabled) {
            $pendingSecret = $user->two_factor_secret ?: $this->totp->generateSecret();

            if ($user->two_factor_secret !== $pendingSecret) {
                $user->forceFill(['two_factor_secret' => $pendingSecret])->save();
            }
        }

        return inertia('settings/security/two-factor', [
            'twoFactorEnabled' => (bool) $user->two_factor_enabled,
            'secret' => $pendingSecret,
            'provisioningUri' => $pendingSecret
                ? $this->totp->provisioningUri($pendingSecret, (string) $user->email, config('app.name'))
                : null,
            'recoveryCodesRemaining' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! $this->totp->verify($user->two_factor_secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'The provided two-factor authentication code is invalid.',
            ]);
        }

        $user->forceFill([
            'two_factor_enabled' => true,
            // Stored hashed: the list is a set of credentials, and the plaintext
            // exists only for the moment it is generated.
            'two_factor_recovery_codes' => $this->recoveryCodes->hashAll($this->totp->recoveryCodes()),
        ])->save();

        return redirect()
            ->route('settings.security.two-factor')
            ->with('success', 'Two-factor authentication enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()
            ->route('settings.security.two-factor')
            ->with('success', 'Two-factor authentication disabled.');
    }
}
