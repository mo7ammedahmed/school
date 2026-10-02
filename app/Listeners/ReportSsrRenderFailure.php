<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Ssr\SsrRenderFailed;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns a failed SSR render into something an operator sees.
 *
 * Inertia treats a broken SSR stack as a performance detail: the page falls back
 * to rendering in the browser and the response is still a 200. The failure is
 * announced only as this event — nothing logs it on its own — so a dead SSR
 * server, a bundle that no deploy rebuilt and a component that throws under Node
 * all look like a working application.
 *
 * A broken server fails on *every* request, so one alert per window carries the
 * fact and the failures behind it are counted instead of written out one per page
 * view. The alert goes to the dedicated `ssr` channel (see config/logging.php):
 * its own file, plus Slack whenever LOG_SLACK_WEBHOOK_URL is set.
 */
class ReportSsrRenderFailure
{
    /**
     * How long one alert stands for. Long enough that a busy site does not page
     * anyone per request, short enough to notice an outage during the workday.
     */
    public const ALERT_WINDOW_SECONDS = 300;

    private const WINDOW_KEY = 'ssr:alerted';

    private const SUPPRESSED_KEY = 'ssr:suppressed';

    /**
     * The count has to outlive the window it describes, or an outage that fails
     * once a minute would report "0 suppressed" at every alert. Twice the window
     * is enough for a steady failure rate and still expires on its own once the
     * failures stop.
     */
    private const SUPPRESSED_TTL_SECONDS = self::ALERT_WINDOW_SECONDS * 2;

    public function handle(SsrRenderFailed $event): void
    {
        // The response is still being built here: this flag is how
        // ReportSsrFallback, which reads the finished HTML later in the same
        // request, knows the gateway has already named this failure.
        request()->attributes->set('ssr.failure_reported', true);

        try {
            if (! Cache::add(self::WINDOW_KEY, now()->getTimestamp(), self::ALERT_WINDOW_SECONDS)) {
                Cache::add(self::SUPPRESSED_KEY, 0, self::SUPPRESSED_TTL_SECONDS);
                Cache::increment(self::SUPPRESSED_KEY);

                return;
            }

            $this->channel()->critical(
                'Inertia SSR render failed — the page fell back to client-side rendering',
                $event->toArray() + ['suppressed_since_last_alert' => (int) Cache::pull(self::SUPPRESSED_KEY, 0)],
            );
        } catch (Throwable $e) {
            // Watching for a failure must not become one. This runs after the
            // page has been rendered, so a cache or log driver that is itself
            // broken must not turn a working page into a 500.
            report($e);
        }
    }

    /**
     * The dedicated channel, or the application default when a config cached
     * before this feature was deployed does not define it.
     */
    private function channel(): LoggerInterface
    {
        return Log::channel(config('logging.channels.ssr') === null ? Log::getDefaultDriver() : 'ssr');
    }
}
