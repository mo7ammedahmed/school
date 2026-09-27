<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Schools\Models\School;
use Database\Factories\Domain\Academics\Models\GradeLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'code',
    'level',
    'description',
    'description_ar',
])]
#[Appends([
    'name',
])]
#[UseFactory(GradeLevelFactory::class)]
class GradeLevel extends Model
{
    /** @use HasFactory<GradeLevelFactory> */
    use HasFactory, SoftDeletes;

    protected function name(): Attribute
    {
        return Attribute::make(get: function () {
            $locale = app()->getLocale();

            return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }
}
