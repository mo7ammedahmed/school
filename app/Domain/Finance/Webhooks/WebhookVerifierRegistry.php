<?php

declare(strict_types=1);

namespace App\Domain\Finance\Webhooks;

/**
 * Maps a URL segment to the one verifier that answers for it.
 *
 * A gateway with no verifier cannot be authenticated, and this registry does not
 * invent one: the controller refuses the delivery outright. That is deliberate —
 * a shared secret that no provider actually sends would be a check in name only.
 * Adding HyperPay or Stripe means adding a verifier here; until then their
 * deliveries are answered with a 404 instead of being trusted.
 */
final class WebhookVerifierRegistry
{
    /** @var array<string, WebhookVerifier> */
    private array $verifiers = [];

    /**
     * @param  list<WebhookVerifier>  $verifiers
     */
    public function __construct(array $verifiers)
    {
        foreach ($verifiers as $verifier) {
            $this->verifiers[$verifier->gateway()] = $verifier;
        }
    }

    public function for(string $gateway): ?WebhookVerifier
    {
        return $this->verifiers[$gateway] ?? null;
    }

    /**
     * @return list<string>
     */
    public function gateways(): array
    {
        return array_keys($this->verifiers);
    }
}
