<?php

declare(strict_types=1);

namespace App\Domain\Identity\Services;

use InvalidArgumentException;

/**
 * Time-based one-time passwords (RFC 6238) built on HMAC-SHA1 (RFC 4226).
 *
 * Implemented in-house rather than pulling a third-party package: the algorithm
 * is small, fully specified, and pinned by the RFC 6238 test vectors covered in
 * tests/Unit/Domain/Identity/TotpServiceTest.php. Secrets are base32 so they can
 * be typed into — or scanned from — any standard authenticator app.
 */
class TotpService
{
    private const string ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const int DIGITS = 6;

    private const int PERIOD = 30;

    /** Number of periods of clock drift accepted either side of "now". */
    private const int WINDOW = 1;

    /**
     * Generate a new base32 secret from cryptographically secure random bytes.
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->encode(random_bytes($bytes));
    }

    /**
     * Compute the code for a given timestamp.
     */
    public function code(
        string $secret,
        ?int $timestamp = null,
        int $period = self::PERIOD,
        int $digits = self::DIGITS,
    ): string {
        $counter = intdiv($timestamp ?? time(), $period);
        $modulo = 10 ** $digits;

        return str_pad((string) ($this->hotp($secret, $counter) % $modulo), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted code, allowing for clock drift on either side.
     */
    public function verify(
        string $secret,
        string $code,
        int $window = self::WINDOW,
        int $period = self::PERIOD,
        int $digits = self::DIGITS,
        ?int $timestamp = null,
    ): bool {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (strlen($code) !== $digits || ! ctype_digit($code)) {
            return false;
        }

        $now = $timestamp ?? time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = $this->code($secret, $now + ($offset * $period), $period, $digits);

            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build an otpauth:// URI so authenticator apps can enrol by QR code.
     */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD,
        );
    }

    /**
     * Generate single-use recovery codes.
     *
     * @return list<string>
     */
    public function recoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }

        return $codes;
    }

    /**
     * RFC 4226 HMAC-based one-time password.
     *
     * The counter is packed as a 64-bit big-endian integer, split into two
     * 32-bit halves so it stays correct on 32-bit PHP builds.
     */
    private function hotp(string $secret, int $counter): int
    {
        $key = $this->decode($secret);
        $binary = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        return
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);
    }

    private function encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private function decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');

        if ($secret === '') {
            throw new InvalidArgumentException('TOTP secret is empty.');
        }

        $bits = '';

        foreach (str_split($secret) as $character) {
            $index = strpos(self::ALPHABET, $character);

            if ($index === false) {
                throw new InvalidArgumentException('TOTP secret is not valid base32.');
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }

            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }
}
