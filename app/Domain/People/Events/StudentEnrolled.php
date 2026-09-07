<?php

declare(strict_types=1);

namespace App\Domain\People\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEnrolled
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $studentId, public int $sectionId, public int $academicYearId)
    {
    }
}
