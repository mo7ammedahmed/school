<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\Services\TotpService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    /** RFC 6238 uses the ASCII secret "12345678901234567890". */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService;
    }

    /**
     * RFC 6238 Appendix B, SHA-1, 8 digits.
     */
    public static function rfcVectors(): array
    {
        return [
            'T=59' => [59, '94287082'],
            'T=1111111109' => [1111111109, '07081804'],
            'T=1111111111' => [1111111111, '14050471'],
            'T=1234567890' => [1234567890, '89005924'],
            'T=2000000000' => [2000000000, '69279037'],
            'T=20000000000' => [20000000000, '65353130'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function test_it_matches_the_rfc_6238_vectors(int $timestamp, string $expected): void
    {
        $this->assertSame($expected, $this->totp->code(self::RFC_SECRET, $timestamp, 30, 8));
    }

    public function test_it_accepts_the_current_code(): void
    {
        $timestamp = 1700000000;
        $code = $this->totp->code(self::RFC_SECRET, $timestamp);

        $this->assertTrue($this->totp->verify(self::RFC_SECRET, $code, 1, 30, 6, $timestamp));
    }

    public function test_it_tolerates_one_period_of_clock_drift_either_side(): void
    {
        $timestamp = 1700000000;
        $previous = $this->totp->code(self::RFC_SECRET, $timestamp - 30);
        $next = $this->totp->code(self::RFC_SECRET, $timestamp + 30);

        $this->assertTrue($this->totp->verify(self::RFC_SECRET, $previous, 1, 30, 6, $timestamp));
        $this->assertTrue($this->totp->verify(self::RFC_SECRET, $next, 1, 30, 6, $timestamp));
    }

    public function test_it_rejects_codes_outside_the_drift_window(): void
    {
        $timestamp = 1700000000;
        $stale = $this->totp->code(self::RFC_SECRET, $timestamp - 300);

        $this->assertFalse($this->totp->verify(self::RFC_SECRET, $stale, 1, 30, 6, $timestamp));
    }

    public function test_it_rejects_malformed_codes(): void
    {
        $this->assertFalse($this->totp->verify(self::RFC_SECRET, 'abcdef'));
        $this->assertFalse($this->totp->verify(self::RFC_SECRET, '12345'));
        $this->assertFalse($this->totp->verify(self::RFC_SECRET, ''));
    }

    public function test_it_ignores_whitespace_in_submitted_codes(): void
    {
        $timestamp = 1700000000;
        $code = $this->totp->code(self::RFC_SECRET, $timestamp);
        $spaced = substr($code, 0, 3).' '.substr($code, 3);

        $this->assertTrue($this->totp->verify(self::RFC_SECRET, $spaced, 1, 30, 6, $timestamp));
    }

    public function test_generated_secrets_are_base32_and_unique(): void
    {
        $first = $this->totp->generateSecret();
        $second = $this->totp->generateSecret();

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $first);
        $this->assertSame(32, strlen($first));
    }

    public function test_a_generated_secret_round_trips_through_verification(): void
    {
        $secret = $this->totp->generateSecret();
        $timestamp = 1700000000;

        $this->assertTrue($this->totp->verify($secret, $this->totp->code($secret, $timestamp), 1, 30, 6, $timestamp));
    }

    public function test_recovery_codes_are_unique_and_uppercase(): void
    {
        $codes = $this->totp->recoveryCodes(8);

        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{10}$/', $code);
        }
    }

    public function test_provisioning_uri_encodes_the_secret_and_issuer(): void
    {
        $uri = $this->totp->provisioningUri('ABCDEF234567', 'staff@example.com', 'Al Noor School');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=ABCDEF234567', $uri);
        $this->assertStringContainsString('issuer=Al%20Noor%20School', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }
}
