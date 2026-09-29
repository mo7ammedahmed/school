<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Attendance\Models\AttendanceRecord;

/**
 * The class name does not name the table.
 *
 * `App\Domain\Attendance\Models\AttendanceRecord` resolves to `attendance_records`
 * on its own, but this alias derives `attendances`, which has never existed — so
 * anything reaching for it failed with "no such table" instead of reading what it
 * asked for. `ModelTablesTest` keeps every alias honest now.
 */
class Attendance extends AttendanceRecord
{
    protected $table = 'attendance_records';
}
