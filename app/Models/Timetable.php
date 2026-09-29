<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Scheduling\Models\TimetableEntry;

/**
 * A timetable row is a row in `timetable_entries`; this alias derives
 * `timetables`, which does not exist. See `ModelTablesTest`.
 */
class Timetable extends TimetableEntry
{
    protected $table = 'timetable_entries';
}
