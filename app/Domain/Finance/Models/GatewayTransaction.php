<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Finance\Models\Payment;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'payment_id',
    'gateway',
    'gateway_transaction_id',
    'status',
    'currency',
    'amount',
    'payload',
    'response',
    'error_message',
])]
class GatewayTransaction extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'payload' => 'array',
            'response' => 'array',
        ];
    }
}
