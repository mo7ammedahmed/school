<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Finance\Services\MoyasarGateway;
use App\Domain\Finance\Services\PaymentSettlementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __construct(
        protected MoyasarGateway $moyasarGateway,
        protected PaymentSettlementService $settlementService,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        abort_unless(in_array($gateway, ['moyasar', 'hyperpay', 'stripe'], true), Response::HTTP_NOT_FOUND);

        $payload = $request->all();
        $this->settlementService->handleWebhook($gateway, $payload);

        return response()->json(['status' => 'success']);
    }

    public function handleMoyasar(Request $request)
    {
        $payload = $request->all();

        Log::info('Moyasar webhook received', ['event_id' => $payload['id'] ?? null]);

        try {
            $this->settlementService->handleWebhook('moyasar', $payload);

            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Log::error('Moyasar webhook handling failed', [
                'error' => $e->getMessage(),
                'event_id' => $payload['id'] ?? null,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function handleHyperPay(Request $request)
    {
        $payload = $request->all();

        Log::info('HyperPay webhook received', ['event_id' => $payload['id'] ?? null]);

        try {
            $this->settlementService->handleWebhook('hyperpay', $payload);

            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Log::error('HyperPay webhook handling failed', [
                'error' => $e->getMessage(),
                'event_id' => $payload['id'] ?? null,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function handleStripe(Request $request)
    {
        $payload = $request->all();

        Log::info('Stripe webhook received', ['event_id' => $payload['id'] ?? null]);

        try {
            $this->settlementService->handleWebhook('stripe', $payload);

            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
                'event_id' => $payload['id'] ?? null,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
