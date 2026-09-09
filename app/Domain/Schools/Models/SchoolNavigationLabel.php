<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'key',
    'name_en',
    'name_ar',
])]
class SchoolNavigationLabel extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
