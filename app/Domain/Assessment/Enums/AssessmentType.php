<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Enums;

enum AssessmentType: string
{
    case Formative = 'formative';
    case Summative = 'summative';
    case Diagnostic = 'diagnostic';
}
