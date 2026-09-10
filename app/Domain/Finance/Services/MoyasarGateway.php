<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarGateway implements PaymentGatewayInterface
{
    protected ?string $apiKey;

    protected ?string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.moyasar.api_key');
        $this->secretKey = config('services.moyasar.secret_key');
        $this->baseUrl = config('services.moyasar.base_url', 'https://api.moyasar.com/v1');
    }

    public function createPayment(Payment $payment, array $metadata = []): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->post("{$this->baseUrl}/payments", [
                    'amount' => (int) ($payment->amount * 100),
                    'currency' => $payment->currency ?? 'SAR',
                    'description' => "Payment for invoice #{$payment->invoice->invoice_number}",
                    'metadata' => array_merge($metadata, [
                        'payment_id' => $payment->id,
                        'invoice_id' => $payment->invoice_id,
                        'student_id' => $payment->student_id,
                    ]),
                ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'transaction_id' => $response->json('id'),
                'status' => $response->json('status'),
            ];
        } catch (Exception $e) {
            Log::error('Moyasar payment creation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifyPayment(string $transactionId): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->get("{$this->baseUrl}/payments/{$transactionId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status' => $response->json('status'),
            ];
        } catch (Exception $e) {
            Log::error('Moyasar payment verification failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount, string $reason = ''): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->post("{$this->baseUrl}/payments/{$transactionId}/refund", [
                    'amount' => (int) ($amount * 100),
                    'reason' => $reason,
                ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Moyasar refund failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getTransactionStatus(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    public function handleWebhook(array $payload): array
    {
        return [
            'success' => true,
            'data' => $payload,
            'transaction_id' => $payload['id'] ?? null,
            'status' => $payload['status'] ?? 'unknown',
        ];
    }
}
