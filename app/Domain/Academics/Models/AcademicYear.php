<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Schools\Models\School;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(AcademicYearFactory::class)]
#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'start_date',
    'end_date',
    'is_current',
])]
#[Appends([
    'name',
])]
class AcademicYear extends Model
{
    use HasFactory, SoftDeletes;

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }
}
