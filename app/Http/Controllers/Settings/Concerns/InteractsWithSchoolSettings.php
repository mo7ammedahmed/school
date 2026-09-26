<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Concerns;

use App\Domain\Schools\Services\SchoolSettingsStore;

/**
 * Shared access to the active school's settings for the Settings screens.
 *
 * Every integration screen owned the same two facts — which school the request
 * is scoped to, and where its settings live — so this keeps them in one place
 * instead of each controller reaching for the session or the table directly.
 */
trait InteractsWithSchoolSettings
{
    protected function settingsSchoolId(): int
    {
        $schoolId = (int) session('school_id');

        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        return $schoolId;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  list<string>  $secrets
     */
    protected function settings(string $key, array $defaults = [], array $secrets = []): SchoolSettingsStore
    {
        return new SchoolSettingsStore($this->settingsSchoolId(), $key, $defaults, $secrets);
    }
}
