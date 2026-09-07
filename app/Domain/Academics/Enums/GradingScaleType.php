<?php

declare(strict_types=1);

namespace App\Domain\Academics\Enums;

enum GradingScaleType: string
{
    case Percentage = 'percentage';
    case Gpa = 'gpa';
    case Letter = 'letter';
}
