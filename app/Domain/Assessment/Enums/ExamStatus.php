<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Published = 'published';
}
