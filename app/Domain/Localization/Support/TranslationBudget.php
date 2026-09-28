<?php

declare(strict_types=1);

namespace App\Domain\Localization\Support;

/**
 * What one run is allowed to spend at the translation provider.
 *
 * Every translated value is a paid call that takes seconds, so a run is bounded
 * by three things at once and each one has a name when it goes wrong: too many
 * calls is an invoice, too many seconds is a screen that looks frozen, and a
 * call that outlives PHP's `max_execution_time` is the fatal
 * "Maximum execution time of 30 seconds exceeded" that killed the request
 * mid-write and left the settings screen on "Translating…" for ever.
 *
 * The sweep and the on-save fill used to carry their own copy of this
 * arithmetic — each with its own `ini_get()` and its own idea of when a call
 * still fits — so they could disagree about the same host. There is one copy
 * now, and it is the only thing in the feature that reads the clock or the
 * host's limit.
 */
final class TranslationBudget
{
    private int $remaining;

    private float $spent = 0.0;

    /**
     * @param  float  $softSeconds  the run's own allowance, read one of two ways
     * @param  float  $mustNotFinishAfter  PHP's own wall clock for the request
     * @param  float  $callCeiling  the longest one provider call may take
     * @param  bool  $softIsDeadline  true when a call must *finish* inside the
     *                                soft allowance (a sweep that has to answer);
     *                                false when the allowance is provider time
     *                                the run may *spend* (filling on save)
     * @param  int  $translations  calls the run may make, or PHP_INT_MAX
     */
    private function __construct(
        private readonly float $startedAt,
        private readonly float $softSeconds,
        private readonly float $mustNotFinishAfter,
        private readonly float $callCeiling,
        private readonly bool $softIsDeadline,
        int $translations,
    ) {
        $this->remaining = max(0, $translations);
    }

    /**
     * A sweep: the configured wall clock, the configured number of values, and
     * whatever the host allows.
     */
    public static function forRun(
        ?float $seconds = null,
        ?int $translations = null,
        ?float $executionLimit = null,
    ): self {
        return self::make(
            soft: $seconds ?? (float) config('bilingual.max_seconds_per_run', 16),
            // A sweep has to come back in time to report progress, so a call it
            // starts has to *finish* inside the allowance.
            softIsDeadline: true,
            translations: $translations ?? (int) config('bilingual.max_translations_per_run', 100),
            executionLimit: $executionLimit,
        );
    }

    /**
     * The share of a request one save may spend filling pairs: the same clocks,
     * a much smaller allowance, and no count because a single save only has a
     * handful of pairs to fill.
     *
     * Here the allowance is provider time the save may *spend*, not a deadline a
     * call has to fit inside: one provider call routinely takes longer than the
     * whole allowance, and asking it to finish within 6 seconds would mean the
     * fill never started a single call.
     */
    public static function forSave(?float $seconds = null, ?float $executionLimit = null): self
    {
        return self::make(
            soft: $seconds ?? (float) config('bilingual.on_save_seconds', 6),
            softIsDeadline: false,
            translations: PHP_INT_MAX,
            executionLimit: $executionLimit,
        );
    }

    private static function make(
        float $soft,
        bool $softIsDeadline,
        int $translations,
        ?float $executionLimit,
    ): self {
        $started = microtime(true);
        $limit = $executionLimit ?? self::executionLimit();

        return new self(
            startedAt: $started,
            softSeconds: max(1.0, $soft),
            // A second of headroom, so the request can still answer.
            mustNotFinishAfter: $limit === null ? PHP_FLOAT_MAX : $started + $limit - 1.0,
            callCeiling: self::callCeiling($limit),
            softIsDeadline: $softIsDeadline,
            translations: $translations,
        );
    }

    /**
     * How long PHP allows this request to run, or null when it sets no limit.
     *
     * Read live rather than assumed: the CLI reports no limit while the server
     * that serves the app enforces 30 seconds.
     */
    public static function executionLimit(): ?float
    {
        $limit = (int) ini_get('max_execution_time');

        return $limit > 0 ? (float) $limit : null;
    }

    /**
     * The longest a single provider call may take. On a host with a tighter
     * limit than the configured timeout, the call is shortened to fit rather
     * than left to be killed.
     */
    private static function callCeiling(?float $limit): float
    {
        $configured = max(1.0, (float) config('bilingual.request_timeout_seconds', 10));

        return $limit === null ? $configured : max(1.0, min($configured, $limit - 3));
    }

    /** The per-call ceiling the provider client is given for this run. */
    public function callSeconds(): float
    {
        return $this->callCeiling;
    }

    /** Provider seconds actually spent, which is what the report quotes. */
    public function spent(): float
    {
        return $this->spent;
    }

    public function remaining(): int
    {
        return $this->remaining;
    }

    public function softSeconds(): float
    {
        return $this->softSeconds;
    }

    /** Seconds left before the run stops starting new calls. */
    public function secondsLeft(): float
    {
        return max(0.0, ($this->startedAt + $this->softSeconds) - microtime(true));
    }

    /**
     * Whether one more call fits.
     *
     * The host's own clock always applies — a call is only begun while it still
     * finishes before PHP's limit, which is what keeps a slow provider from
     * taking the request down with it. The run's own allowance then applies
     * according to its shape: a deadline a sweep's next call must finish inside,
     * or an amount of provider time the fill may still spend.
     */
    public function canStartCall(): bool
    {
        if ($this->remaining <= 0) {
            return false;
        }

        $finishes = microtime(true) + $this->callCeiling;

        if ($finishes >= $this->mustNotFinishAfter) {
            return false;
        }

        return $this->softIsDeadline
            ? $finishes < $this->startedAt + $this->softSeconds
            : $this->spent < $this->softSeconds;
    }

    /** Records a call that was begun: one from the count, its seconds from the clock. */
    public function spend(float $seconds = 0.0): void
    {
        $this->remaining = max(0, $this->remaining - 1);
        $this->spent += max(0.0, $seconds);
    }
}
