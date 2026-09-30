<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Services\RecoveryCodes;
use App\Domain\Identity\Services\TotpService;
use App\Http\Controllers\Auth\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * The second half of a two-factor sign-in.
 *
 * The visitor here is deliberately *not* authenticated: the login controller
 * parked the identity in the session once the password checked out, and this
 * controller is what turns that pending sign-in into a session. Reading
 * `$request->user()` — as this class used to — answers only a user who is
 * already signed in, which is the one state the screen must never serve.
 */
class TwoFactorAuthenticationController extends Controller
{
    use ThrottlesAttempts;

    public function __construct(
        private readonly TotpService $totp,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->pendingUserId($request) === null) {
            return redirect()->route('login');
        }

        return inertia('auth/two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $this->pendingUserId($request);

        if ($userId === null) {
            return redirect()->route('login');
        }

        // Six digits is a small space: without a limit the entire range is a
        // few minutes of scripted requests.
        $key = $this->attemptKey('two-factor', $request, (string) $userId);

        $this->ensureIsNotRateLimited($key, 'code');

        $user = User::query()->find($userId);

        if ($user === null || ! $user->two_factor_enabled) {
            $this->forgetPending($request);

            return redirect()->route('login');
        }

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';

        $secret = $user->two_factor_secret;
        $isValidCode = is_string($secret) && $secret !== '' && $this->totp->verify($secret, $code);

        if (! $isValidCode && ! $this->recoveryCodes->consume($user, $code)) {
            $this->hitAttempts($key);

            throw ValidationException::withMessages([
                'code' => 'The provided code is invalid.',
            ]);
        }

        $this->clearAttempts($key);

        $remember = (bool) $request->session()->pull('auth.two_factor_remember', false);

        $this->forgetPending($request);

        Auth::login($user, $remember);

        $request->session()->regenerate();
        $request->session()->forget('school_id');

        return redirect()->route('school.select');
    }

    private function pendingUserId(Request $request): ?int
    {
        $value = $request->session()->get('auth.two_factor_user_id');

        return is_numeric($value) ? (int) $value : null;
    }

    private function forgetPending(Request $request): void
    {
        $request->session()->forget(['auth.two_factor_user_id', 'auth.two_factor_remember']);
    }
}
