<?php

declare(strict_types=1);

namespace App\Domain\Academics\Enums;

enum EnrollmentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Transferred = 'transferred';
}
