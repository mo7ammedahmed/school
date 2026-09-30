<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    use ThrottlesAttempts;

    public function create()
    {
        return inertia('auth/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = $this->attemptKey('login', $request, $credentials['email']);

        $this->ensureIsNotRateLimited($key, 'email');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->hitAttempts($key);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->clearAttempts($key);

        $user = Auth::user();

        // A password proves who the user is; it does not prove they hold the
        // second factor. When one is enrolled, the sign-in is parked in the
        // session and only the challenge can finish it.
        if ($user instanceof User && $user->two_factor_enabled && is_string($user->two_factor_secret) && $user->two_factor_secret !== '') {
            return $this->beginTwoFactorChallenge($request, $user);
        }

        return $this->completeSignIn($request);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Park the sign-in: no authenticated session yet, just the identity the
     * challenge is for and whether the user asked to be remembered.
     */
    private function beginTwoFactorChallenge(Request $request, User $user): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->put('auth.two_factor_user_id', $user->id);
        $request->session()->put('auth.two_factor_remember', $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('two-factor.login');
    }

    private function completeSignIn(Request $request): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->forget('school_id');

        return redirect()->route('school.select');
    }
}
