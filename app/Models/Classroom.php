<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Scheduling\Models\Room;

/**
 * A classroom is a row in `rooms`; this alias derives `classrooms`, which does
 * not exist. See `ModelTablesTest`.
 */
class Classroom extends Room
{
    protected $table = 'rooms';
}
