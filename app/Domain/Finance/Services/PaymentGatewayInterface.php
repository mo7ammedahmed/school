<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Payment;

interface PaymentGatewayInterface
{
    public function createPayment(Payment $payment, array $metadata = []): array;

    public function verifyPayment(string $transactionId): array;

    public function refund(string $transactionId, float $amount, string $reason = ''): array;

    public function getTransactionStatus(string $transactionId): array;

    public function handleWebhook(array $payload): array;
}
