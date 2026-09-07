<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum EventType: string
{
    case Academic = 'academic';
    case Sports = 'sports';
    case Cultural = 'cultural';
    case Holiday = 'holiday';
    case Meeting = 'meeting';
}
