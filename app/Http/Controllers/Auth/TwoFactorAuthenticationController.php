<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Services\TotpService;
use App\Http\Controllers\Auth\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class TwoFactorAuthenticationController extends Controller
{
    use ThrottlesAttempts;

    public function __construct(private readonly TotpService $totp) {}

    public function create(): Response
    {
        return inertia('auth/two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Six digits is a small space: without a limit the entire range is a
        // few minutes of scripted requests.
        $key = $this->attemptKey('two-factor', $request, (string) $user->id);

        $this->ensureIsNotRateLimited($key, 'code');

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';

        $secret = $user->two_factor_secret;
        $isValidCode = is_string($secret) && $secret !== '' && $this->totp->verify($secret, $code);

        if (! $isValidCode && ! $this->consumeRecoveryCode($user, $code)) {
            $this->hitAttempts($key);

            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid.'],
            ]);
        }

        $this->clearAttempts($key);

        $request->session()->put('auth.two_factor_confirmed', true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Consume a single-use recovery code, removing it once it has been used.
     */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        if ($code === '' || ! in_array($code, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_diff($codes, [$code]));

        $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();

        return true;
    }
}
