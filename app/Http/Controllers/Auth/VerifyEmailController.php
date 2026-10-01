<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifyEmailController extends Controller
{
    /**
     * The page the `verified` middleware sends an unverified user to, and the
     * one the login screen's "resend" link points at. It is the *signed-in*
     * half of email verification: {@see self::__invoke()} is the other half,
     * and reads the signed link a verification mail carries.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // `auth` guarantees a user, not the `MustVerifyEmail` contract this
        // route's methods come from; without the check a user model that does
        // not verify emails dies on a call to a method it never had.
        abort_unless($user instanceof MustVerifyEmail, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('dashboard')->with('status', 'verification-link-sent');
    }
}
