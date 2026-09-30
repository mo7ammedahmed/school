<?php

declare(strict_types=1);

namespace App\Domain\Finance\Webhooks;

/**
 * What the webhook endpoint did with a delivery, and what it should answer.
 *
 * The HTTP status is part of the value because it is a protocol decision, not a
 * presentation one: a delivery we could not process (500) is one the provider
 * should send again, while a delivery we refused (401, 422) is one it should
 * not. Moyasar retries a non-2xx five times, so answering 500 too eagerly turns
 * a permanent mismatch into a retry storm.
 */
final readonly class WebhookResult
{
    private function __construct(
        public int $httpStatus,
        public string $outcome,
        public string $message = '',
    ) {}

    public static function settled(): self
    {
        return new self(200, 'settled');
    }

    /**
     * A delivery already completed. Answering 2xx keeps the provider from
     * retrying something that will not change.
     */
    public static function replayed(): self
    {
        return new self(200, 'replayed');
    }

    /**
     * A delivery we deliberately did not act on and do not want retried:
     * nothing matched it, or the gateway does not report it as money.
     */
    public static function ignored(string $message): self
    {
        return new self(200, 'ignored', $message);
    }

    /**
     * A delivery we refuse: unverifiable, malformed, or inconsistent with the
     * local record.
     */
    public static function rejected(int $httpStatus, string $message): self
    {
        return new self($httpStatus, 'rejected', $message);
    }

    /**
     * A delivery that could not be processed but might succeed on another
     * attempt — an unreachable gateway, a database failure.
     */
    public static function retryable(string $message): self
    {
        return new self(500, 'retry', $message);
    }
}
