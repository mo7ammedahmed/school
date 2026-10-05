<?php

use App\Console\Commands\FinalizeLiveSessions;
use App\Console\Commands\PruneLiveRecordings;
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
//
// `onOneServer` is for the deployments where one application answers on many
// replicas — Laravel Cloud among them, where the scheduler runs on every one of
// them. Both tasks are safe to repeat, but not free: each sweep re-reads the
// recordings disk, and a lesson must not be published twice.
Schedule::command(FinalizeLiveSessions::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// The recordings disk is a stopping point, not storage. Publish what can be
// published, then delete what is left over before it outgrows the volume. See
// the command for the two-part rule.
Schedule::command(PruneLiveRecordings::class)
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
