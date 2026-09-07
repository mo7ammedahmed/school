<?php

declare(strict_types=1);

namespace App\Domain\Learning\Enums;

enum SubmissionStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Graded = 'graded';
    case Returned = 'returned';
}
