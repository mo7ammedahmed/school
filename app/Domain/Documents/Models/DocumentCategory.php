<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name',
    'name_ar',
    'code',
    'description',
    'description_ar',
])]
class DocumentCategory extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
