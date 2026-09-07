<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'gateway',
    'event_id',
    'event_type',
    'payload',
    'status',
    'error_message',
])]
class WebhookEvent extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
