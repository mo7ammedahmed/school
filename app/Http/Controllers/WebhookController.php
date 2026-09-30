<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Finance\Services\PaymentSettlementService;
use App\Domain\Finance\Webhooks\WebhookVerifierRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives payment-gateway deliveries.
 *
 * The endpoint is public by necessity — gateways cannot sign in — so every
 * delivery is authenticated by the school's own webhook secret before anything
 * is written. A gateway with no verifier is refused with a 404 rather than
 * trusted: an unconditional acceptance is how a forged "payment succeeded"
 * settles an invoice.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookVerifierRegistry $verifiers,
        private readonly PaymentSettlementService $settlementService,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $verifier = $this->verifiers->for($gateway);

        if ($verifier === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $result = $this->settlementService->handleWebhook($gateway, $request->all(), $verifier);

        return response()->json(['status' => $result->outcome], $result->httpStatus);
    }
}
