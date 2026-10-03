<?php

use App\Console\Commands\FinalizeLiveSessions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reconcile live sessions the app lost track of: a teacher whose browser closed
// without ending the lesson, or a recording whose MediaMTX hook never arrived.
// Both are idempotent; the command dispatches the same finalize job the hook
// does. Runs from the scheduler container the compose stack already starts.
Schedule::command(FinalizeLiveSessions::class)->everyFiveMinutes()->withoutOverlapping();
