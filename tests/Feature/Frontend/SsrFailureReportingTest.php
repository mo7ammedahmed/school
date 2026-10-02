<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use App\Domain\Schools\Models\School;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ReportSsrFallback;
use App\Listeners\ReportSsrRenderFailure;
use ArrayObject;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\Response as SsrResponse;
use Inertia\Ssr\SsrErrorType;
use Inertia\Ssr\SsrRenderFailed;
use RuntimeException;
use Tests\TestCase;

/**
 * A missing or broken SSR stack is invisible: Inertia renders the page in the
 * browser instead, the response is still a 200 and the deploy still looks green.
 * Two things make it visible — the listener on Inertia's own failure event, and
 * the middleware that catches the failures Inertia never announces at all (a
 * bundle no deploy built, a body that is not JSON) by reading the HTML that was
 * actually sent.
 */
class SsrFailureReportingTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_HTML = '<html><body><script data-page="app" type="application/json">'
        .'{"component":"dashboard","url":"/dashboard","props":{"students":[]}}</script>'
        .'<div id="app"></div></body></html>';

    /**
     * The path Inertia takes when the SSR server answers badly: it announces the
     * failure itself, and the alert must name the page and the reason.
     */
    public function test_a_broken_render_is_alerted_with_the_page_it_broke(): void
    {
        $this->actingAsSchoolUser(School::factory()->create(), ['manage-settings']);

        Http::fake([
            'http://127.0.0.1:13714/*' => Http::response(['error' => 'Node render timed out', 'type' => 'render'], 500),
        ]);

        // The bundle is a build artifact and is absent on a machine that never
        // built one; asking the gateway to skip that check keeps this test about
        // the failure path rather than about this checkout.
        config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);

        $alerts = $this->captureAlerts();

        $this->get('/settings/school')->assertOk();

        $this->assertCount(1, $alerts, 'A failed render must be reported, exactly once.');
        $this->assertSame('critical', $alerts[0]->level);
        $this->assertSame('settings/school/edit', $alerts[0]->context['component']);
        $this->assertSame('Node render timed out', $alerts[0]->context['error']);
        $this->assertSame('render', $alerts[0]->context['type']);
        $this->assertSame(
            0,
            $alerts[0]->context['suppressed_since_last_alert'],
            'The gateway named this failure; the middleware must not report the same request again.',
        );
    }

    /**
     * The paths that say nothing: the gateway returns null and the page quietly
     * renders in the browser. Nothing else in the stack would notice.
     */
    public function test_a_silent_fallback_is_alerted_with_the_page_that_fell_back(): void
    {
        $this->actingAsSchoolUser(School::factory()->create(), ['manage-settings']);

        $this->app->bind(Gateway::class, fn (): Gateway => new class implements Gateway
        {
            public function dispatch(array $page): ?SsrResponse
            {
                return null;
            }
        });

        config(['inertia.ssr.enabled' => true]);

        $alerts = $this->captureAlerts();

        $this->get('/settings/school')->assertOk();

        $this->assertCount(1, $alerts);
        $this->assertSame('settings/school/edit', $alerts[0]->context['component']);
        $this->assertStringContainsString('no server-rendered markup', $alerts[0]->context['error']);
        $this->assertStringContainsString('inertia:start-ssr', (string) $alerts[0]->context['hint']);
    }

    /**
     * A broken SSR server fails every request. One alert carries the fact and the
     * failures behind it are counted — otherwise an outage pages a team once per
     * page view, which is how an alert gets muted.
     */
    public function test_one_alert_stands_for_the_failures_that_follow_it(): void
    {
        $alerts = $this->captureAlerts();

        $fail = fn () => event(new SsrRenderFailed(['component' => 'dashboard', 'url' => '/dashboard'], 'Node render failed', SsrErrorType::Render));

        $fail();
        $this->travel(10)->seconds();
        $fail();

        $this->assertCount(1, $alerts);
        $this->assertSame(0, $alerts[0]->context['suppressed_since_last_alert']);

        // Past the window the alert is due again, and it reports what it stood
        // for in the meantime.
        $this->travel(ReportSsrRenderFailure::ALERT_WINDOW_SECONDS)->seconds();
        $fail();

        $this->assertCount(2, $alerts);
        $this->assertSame(1, $alerts[1]->context['suppressed_since_last_alert']);
    }

    public function test_a_page_is_not_reported_when_ssr_is_off(): void
    {
        $this->actingAsSchoolUser(School::factory()->create(), ['manage-settings']);

        $alerts = $this->captureAlerts();

        $this->get('/settings/school')->assertOk();

        $this->assertCount(0, $alerts, 'Rendering client-side is the point of INERTIA_SSR_ENABLED=false.');
    }

    public function test_the_fallback_middleware_reports_an_inertia_page_without_the_flag(): void
    {
        Event::fake([SsrRenderFailed::class]);
        config(['inertia.ssr.enabled' => true]);

        $this->passThroughMiddleware(self::PAGE_HTML);

        Event::assertDispatched(
            SsrRenderFailed::class,
            fn (SsrRenderFailed $event): bool => $event->component() === 'dashboard' && $event->url() === '/dashboard',
        );
    }

    public function test_the_fallback_middleware_leaves_a_server_rendered_page_alone(): void
    {
        Event::fake([SsrRenderFailed::class]);
        config(['inertia.ssr.enabled' => true]);

        $this->passThroughMiddleware(str_replace('<div id="app">', '<div data-server-rendered="true" id="app">', self::PAGE_HTML));

        Event::assertNotDispatched(SsrRenderFailed::class);
    }

    public function test_the_fallback_middleware_ignores_pages_that_never_had_ssr(): void
    {
        Event::fake([SsrRenderFailed::class]);
        config(['inertia.ssr.enabled' => true]);

        // A Blade page (the invoice payer, an error page) has no mount point, and
        // an Inertia visit answers JSON: neither was ever going to be rendered.
        $this->passThroughMiddleware('<html><body><h1>Invoice</h1></body></html>');
        $this->passThroughMiddleware('{"component":"dashboard"}', 'application/json');

        Event::assertNotDispatched(SsrRenderFailed::class);
    }

    public function test_the_fallback_middleware_ignores_a_response_that_was_already_reported(): void
    {
        Event::fake([SsrRenderFailed::class]);
        config(['inertia.ssr.enabled' => true]);

        $request = Request::create('/dashboard');
        $request->attributes->set('ssr.failure_reported', true);

        $this->passThroughMiddleware(self::PAGE_HTML, 'text/html', $request);

        Event::assertNotDispatched(SsrRenderFailed::class);
    }

    /**
     * The alert is written after the page has been rendered. A cache or log
     * driver that is itself broken must not turn a working page into a 500 —
     * which is the one way this feature could make an outage worse.
     */
    public function test_a_broken_alert_destination_does_not_break_the_page(): void
    {
        config(['inertia.ssr.enabled' => true]);
        Cache::shouldReceive('add')->andThrow(new RuntimeException('cache is down'));

        $response = (new ReportSsrFallback)->handle(
            Request::create('/dashboard'),
            fn () => response(self::PAGE_HTML, 200, ['Content-Type' => 'text/html']),
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * The middleware reads the HTML that HandleInertiaRequests renders on its way
     * out, so it has to sit outside it — any further in, the page is still an
     * Inertia response object and there is nothing to read.
     */
    public function test_the_fallback_middleware_wraps_the_inertia_middleware(): void
    {
        $group = $this->app->make(Kernel::class)->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(HandleInertiaRequests::class, $group);
        $this->assertContains(ReportSsrFallback::class, $group);
        $this->assertLessThan(
            array_search(HandleInertiaRequests::class, $group, true),
            array_search(ReportSsrFallback::class, $group, true),
        );
    }

    public function test_the_listener_is_registered_for_inertia_ssr_failures(): void
    {
        $this->assertTrue(
            $this->app['events']->hasListeners(SsrRenderFailed::class),
            'Nothing is logged unless discovery registers App\Listeners\ReportSsrRenderFailure.',
        );
    }

    public function test_the_alert_channel_is_configured(): void
    {
        $channel = config('logging.channels.ssr');

        $this->assertIsArray($channel, 'The listener writes to the `ssr` channel.');
        $this->assertContains('ssr_file', $channel['channels']);
        $this->assertSame(
            storage_path('logs/ssr.log'),
            config('logging.channels.ssr_file.path'),
            'SSR alerts get their own file: they are operational news, not application noise.',
        );
    }

    /**
     * Every record written through any channel, so a test can assert on what an
     * operator would find in the log.
     *
     * @return ArrayObject<int, MessageLogged>
     */
    private function captureAlerts(): ArrayObject
    {
        $alerts = new ArrayObject;

        Event::listen(MessageLogged::class, function (MessageLogged $event) use ($alerts): void {
            if ($event->level === 'critical') {
                $alerts->append($event);
            }
        });

        return $alerts;
    }

    private function passThroughMiddleware(string $body, string $contentType = 'text/html; charset=UTF-8', ?Request $request = null): void
    {
        (new ReportSsrFallback)->handle(
            $request ?? Request::create('/dashboard'),
            fn () => response($body, 200, ['Content-Type' => $contentType]),
        );
    }
}
