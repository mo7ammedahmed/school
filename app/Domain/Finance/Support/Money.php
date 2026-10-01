<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

/**
 * Minor units — halalas, cents, fils — for a monetary amount.
 *
 * A gateway is told an amount as an integer number of minor units and answers
 * with the same. The conversion appeared in three places, and two of them used
 * `(int) ($amount * 100)` while the third used `round()`. That difference is not
 * cosmetic:
 *
 *   8.20 * 100  is  819.9999999999999  in binary floating point
 *   (int) 819.9999999999999  is  819
 *   round(8.20 * 100)        is  820
 *
 * So an 8.20 payment was charged 819 halalas, the gateway confirmed 819, and
 * settlement — expecting 820 — called it a mismatch and refused to settle a
 * payment the customer had completed. 137 of the first 2000 two-decimal amounts
 * truncate this way, so it is ordinary money, not an edge case.
 *
 * One conversion, used by both sides of the conversation, is the fix. Two sides
 * computing the same number two different ways is the actual defect, and a
 * rounding change to one of them would have silently reintroduced it.
 *
 * The reverse direction — minor units back to a major-unit amount for a decimal
 * column — is left where it is. It rounds to the column's own scale, which is
 * what that write needs.
 */
final class Money
{
    /**
     * The number of minor units in a major-unit amount.
     *
     * Rounded rather than truncated, so the nearest halala is sent. Truncating
     * systematically charges the customer slightly less than the invoice says,
     * which is both wrong and impossible for them to reconcile.
     */
    public static function toMinorUnits(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
