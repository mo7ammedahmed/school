<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use App\Domain\Schools\Support\BelongsToSchool;
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
    use BelongsToSchool;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
