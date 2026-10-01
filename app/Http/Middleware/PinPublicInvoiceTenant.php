<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Schools\Support\TenantContext;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pins the tenant of a signed public invoice link.
 *
 * The `/pay/{invoice}` pages sit outside the auth group: the signature in the
 * URL is the access proof, and the invoice belongs to whichever school issued
 * it — not to the request's fallback school. The global tenant scope is live on
 * `Invoice`, so the route model binding would otherwise look for the invoice
 * inside the wrong school and 404 a link the school itself emailed.
 *
 * The lookup here is deliberately unscoped, because the signature has already
 * proven the caller may see this invoice; the school that owns it is then pinned
 * before anything else runs, so every query after this middleware — including
 * the controller's own binding — is inside the right tenant.
 */
class PinPublicInvoiceTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->route('invoice');

        if ($value !== null) {
            $invoice = Invoice::withoutSchoolScope()->whereKey($value)->first();

            if ($invoice === null) {
                throw (new ModelNotFoundException)->setModel(Invoice::class, [$value]);
            }

            app(TenantContext::class)->set((int) $invoice->school_id);
        }

        return $next($request);
    }
}
