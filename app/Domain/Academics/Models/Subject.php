<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'code',
    'subject_type',
    'description',
])]
#[Appends([
    'name',
])]
class Subject extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_$locale"} ?? $this->name_en;
    }
}
