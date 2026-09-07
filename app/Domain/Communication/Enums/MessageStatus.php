<?php

declare(strict_types=1);

namespace App\Domain\Communication\Enums;

enum MessageStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Read = 'read';
}
