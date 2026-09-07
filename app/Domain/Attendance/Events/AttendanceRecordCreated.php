<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecordCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $recordId, public int $studentId, public string $status)
    {
    }
}
