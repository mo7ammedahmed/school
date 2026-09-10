<?php

declare(strict_types=1);

namespace App\Domain\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $refundId, public int $paymentId, public float $amount) {}
}
