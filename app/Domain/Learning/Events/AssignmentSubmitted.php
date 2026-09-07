<?php

declare(strict_types=1);

namespace App\Domain\Learning\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $submissionId, public int $assignmentId, public int $studentId)
    {
    }
}
