<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use App\Models\User;

/**
 * Storage and comparison for two-factor recovery codes.
 *
 * A recovery code is a credential: whoever holds one can finish a sign-in. They
 * were stored as plaintext (the column is encrypted, but encryption at rest is
 * not a substitute for not keeping the secret) and compared with `in_array`,
 * a scan that stops at the first byte that differs. They are now SHA-256
 * hashes, compared with `hash_equals`.
 *
 * Codes issued before this change were stored in the clear. They keep working —
 * the comparison falls back to the plaintext value — and are re-hashed the first
 * time one is spent, so nobody is locked out by the change.
 */
final class RecoveryCodes
{
    public function hash(string $code): string
    {
        return hash('sha256', $this->normalise($code));
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    public function hashAll(array $codes): array
    {
        return array_map(fn (string $code): string => $this->hash($code), $codes);
    }

    public function matches(string $stored, string $code): bool
    {
        $normalised = $this->normalise($code);

        if ($normalised === '') {
            return false;
        }

        if ($this->isHashed($stored)) {
            return hash_equals($stored, $this->hash($normalised));
        }

        return hash_equals($stored, $normalised);
    }

    /**
     * Spend a code, returning what is left of the set, or null when it matches
     * none of them.
     *
     * @param  array<int, mixed>  $stored
     * @return list<string>|null
     */
    public function consumeFrom(array $stored, string $code): ?array
    {
        foreach ($stored as $index => $entry) {
            if (! is_string($entry) || ! $this->matches($entry, $code)) {
                continue;
            }

            $remaining = $stored;
            unset($remaining[$index]);

            return array_values($remaining);
        }

        return null;
    }

    /**
     * Spend one of the user's codes, re-hashing the remainder so a legacy
     * plaintext set is upgraded the moment it is first used.
     */
    public function consume(User $user, string $code): bool
    {
        $remaining = $this->consumeFrom($user->two_factor_recovery_codes ?? [], $code);

        if ($remaining === null) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => $this->hashAll($remaining)])->save();

        return true;
    }

    public function isHashed(string $value): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }

    /**
     * Uppercase, letters and digits only: a code is read from a screen and typed
     * by hand, so its stored form must not depend on how it was punctuated.
     */
    private function normalise(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }
}
