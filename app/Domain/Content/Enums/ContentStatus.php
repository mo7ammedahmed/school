<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
