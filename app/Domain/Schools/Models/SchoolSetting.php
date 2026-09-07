<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'key',
    'value',
    'type',
])]
class SchoolSetting extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
