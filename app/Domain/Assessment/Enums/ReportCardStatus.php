<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Enums;

enum ReportCardStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
