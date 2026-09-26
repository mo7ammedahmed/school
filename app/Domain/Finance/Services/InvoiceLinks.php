<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Builds the links we hand to guardians. The public payment link is a signed,
 * expiring URL so an unauthenticated guardian can pay without an account while
 * still proving we issued the link.
 */
class InvoiceLinks
{
    public const PAY_LINK_TTL_DAYS = 60;

    public static function payUrl(Invoice $invoice, ?Carbon $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'public.invoices.pay',
            $expiresAt ?? Carbon::now()->addDays(self::PAY_LINK_TTL_DAYS),
            ['invoice' => $invoice->id],
        );
    }

    /**
     * A relative path version that survives a change of APP_URL between the
     * request that built the email and the guardian clicking it.
     */
    public static function payPath(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'public.invoices.pay',
            Carbon::now()->addDays(self::PAY_LINK_TTL_DAYS),
            ['invoice' => $invoice->id],
            absolute: false,
        );
    }

    public static function staffUrl(Invoice $invoice): string
    {
        return route('finance.invoices.show', ['invoice' => $invoice->id]);
    }
}
