<?php

declare(strict_types=1);

namespace App\Domain\Finance\Webhooks;

/**
 * Answers one question for one gateway: was this delivery signed by the secret
 * the school itself configured?
 *
 * A verifier never touches the database or decides what a delivery means. It is
 * handed the decoded body and the school's stored secret, and it answers with a
 * boolean — constant-time where the provider allows it, because comparing
 * secrets with `===` leaks their length and prefix.
 */
interface WebhookVerifier
{
    /**
     * The gateway key this verifier answers for, as it appears in the URL.
     */
    public function gateway(): string;

    /**
     * @param  array<string, mixed>  $payload  the delivery, decoded exactly as the gateway sent it
     * @param  string  $secret  the school's own webhook secret, already decrypted
     */
    public function verify(array $payload, string $secret): bool;
}
