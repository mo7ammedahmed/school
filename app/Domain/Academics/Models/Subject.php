<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Database\Factories\Domain\Academics\Models\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `name` is an accessor over `name_en`/`name_ar`, not a column.
 *
 * @property string $name
 */
#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'code',
    'subject_type',
    'description',
    'description_ar',
    'grade_level_id',
])]
#[Appends([
    'name',
])]
#[UseFactory(SubjectFactory::class)]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use BelongsToSchool, HasFactory, SoftDeletes;

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

    protected function name(): Attribute
    {
        return Attribute::make(get: function () {
            $locale = app()->getLocale();

            return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
        });
    }
}
