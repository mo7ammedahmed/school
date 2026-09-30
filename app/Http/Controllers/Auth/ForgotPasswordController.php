<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ThrottlesAttempts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use ThrottlesAttempts;

    public function create()
    {
        return inertia('auth/forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Counted before the send: the point is to stop the endpoint being used
        // to flood one inbox, so a valid address is not a reason to skip it.
        $key = $this->attemptKey('password-email', $request, (string) $request->input('email'));

        $this->ensureIsNotRateLimited($key, 'email');
        $this->hitAttempts($key);

        Password::sendResetLink(
            $request->only('email')
        );

        return back()->with('status', __('passwords.sent'));
    }
}
