<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processed = 'processed';
}
