<?php

declare(strict_types=1);

namespace App\Domain\Finance\Webhooks;

/**
 * Moyasar's webhook object carries a `secret_token` assigned by the consumer —
 * the merchant sets it when registering the endpoint, and Moyasar echoes it
 * back on every delivery. The documented check is to compare that field with
 * the value the merchant configured, so that is what this verifier does.
 *
 * @see https://docs.moyasar.com/api/other/webhooks/webhook-reference
 */
final class MoyasarWebhookVerifier implements WebhookVerifier
{
    public function gateway(): string
    {
        return 'moyasar';
    }

    public function verify(array $payload, string $secret): bool
    {
        $token = $payload['secret_token'] ?? null;

        if (! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($secret, $token);
    }
}
