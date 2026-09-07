<?php

declare(strict_types=1);

namespace App\Domain\Academics\Enums;

enum OfferingStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
