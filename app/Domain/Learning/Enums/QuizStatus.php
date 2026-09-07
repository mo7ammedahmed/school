<?php

declare(strict_types=1);

namespace App\Domain\Learning\Enums;

enum QuizStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
}
