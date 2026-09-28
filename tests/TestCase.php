<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The on-save fill declines to run in a console, because that is how an
        // import or a command is kept from translating a thousand rows behind
        // the operator's back. The suite is not a bulk run in that sense: it
        // stands in for the browser, so the flag is on here and the ordinary
        // request path is what the tests exercise — the observer is covered by
        // the same saves the pages make, not only by tests that switch it on
        // for themselves.
        config(['bilingual.autofill_in_console' => true]);

        // No test may reach a translation provider (or anything else outside)
        // by accident: a request with no matching fake fails loudly instead.
        // A catch-all `Http::fake()` would be wrong here — stubs are matched in
        // the order they were registered, so one installed in `setUp` would
        // shadow the url-specific fake a test installs afterwards.
        Http::preventStrayRequests();
    }
}
