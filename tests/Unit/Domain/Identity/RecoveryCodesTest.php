<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\Services\RecoveryCodes;
use PHPUnit\Framework\TestCase;

/**
 * Recovery codes were stored as plaintext (encrypted, but plaintext) and
 * compared with `in_array` — a linear scan that stops at the first byte that
 * differs. They are single-use credentials, so they belong hashed, and the
 * comparison belongs in constant time.
 */
class RecoveryCodesTest extends TestCase
{
    public function test_a_code_is_stored_as_a_hash(): void
    {
        $codes = new RecoveryCodes;

        $hashed = $codes->hashAll(['ABCDE12345', 'FGHIJ67890']);

        $this->assertCount(2, $hashed);
        $this->assertSame(64, strlen($hashed[0]));
        $this->assertNotContains('ABCDE12345', $hashed);
    }

    public function test_a_code_matches_its_hash_and_only_its_hash(): void
    {
        $codes = new RecoveryCodes;

        $hashed = $codes->hashAll(['ABCDE12345', 'FGHIJ67890']);

        $this->assertTrue($codes->matches($hashed[0], 'ABCDE12345'));
        $this->assertFalse($codes->matches($hashed[0], 'FGHIJ67890'));
        $this->assertTrue($codes->matches($hashed[1], 'fghij-67890'), 'Codes are normalised before comparison.');
    }

    public function test_consuming_a_code_removes_it_and_leaves_the_rest(): void
    {
        $codes = new RecoveryCodes;

        $hashed = $codes->hashAll(['ABCDE12345', 'FGHIJ67890']);

        $remaining = $codes->consumeFrom($hashed, 'ABCDE12345');

        $this->assertNotNull($remaining);
        $this->assertCount(1, $remaining);
        $this->assertTrue($codes->matches($remaining[0], 'FGHIJ67890'));
        $this->assertNull($codes->consumeFrom($remaining, 'ABCDE12345'), 'A spent code must not work twice.');
    }

    public function test_a_legacy_plaintext_code_still_matches(): void
    {
        $codes = new RecoveryCodes;

        $this->assertTrue(
            $codes->matches('ABCDE12345', 'ABCDE12345'),
            'Codes stored before hashing must keep working, so the change does not lock anyone out.',
        );
    }
}
