<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TwoFactorAuthenticationController extends Controller
{
    public function create()
    {
        return inertia('auth/two-factor-challenge');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (!Auth::validateOtp($request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid.'],
            ]);
        }

        $request->session()->put('auth.two_factor_confirmed', true);

        return redirect()->intended('/dashboard');
    }
}
