<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'academic_year_id',
    'name_ar',
    'name_en',
    'code',
    'start_date',
    'end_date',
    'is_current',
])]
#[Appends([
    'name',
])]
class Semester extends Model
{
    use SoftDeletes;

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
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
