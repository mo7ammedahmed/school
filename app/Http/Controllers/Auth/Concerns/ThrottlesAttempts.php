<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A shared attempt limiter for the authentication screens.
 *
 * The key pairs the identity (the email being tried, or the pending user) with
 * the caller's address, so one attacker cannot lock an account out from a
 * distance and one address cannot work through a list of accounts unnoticed.
 * Five attempts a minute is the limit; a *success* clears the counter, and the
 * sixth attempt is refused even when it is correct — otherwise the counter is
 * decoration.
 */
trait ThrottlesAttempts
{
    /** Attempts allowed per identity and address. */
    protected int $maxAttempts = 5;

    /** The window those attempts are counted over, in seconds. */
    protected int $decaySeconds = 60;

    protected function attemptKey(string $prefix, Request $request, string $identity): string
    {
        return $prefix.'|'.Str::lower($identity).'|'.$request->ip();
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(string $key, string $field): void
    {
        if (! RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => sprintf(
                'Too many attempts. Please try again in %d seconds.',
                RateLimiter::availableIn($key),
            ),
        ]);
    }

    protected function hitAttempts(string $key): void
    {
        RateLimiter::hit($key, $this->decaySeconds);
    }

    protected function clearAttempts(string $key): void
    {
        RateLimiter::clear($key);
    }
}
