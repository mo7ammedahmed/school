<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Semester;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `max_score` is `->nullable()` in the schema; the analyser types it as a plain
 * numeric string and calls the null check on it impossible.
 *
 * @property string|null $max_score
 * @property-read GradingCategory|null $gradingCategory
 * @property-read Offering|null $offering
 * @property-read Collection<int, AssessmentScore> $scores
 */
#[Fillable([
    'school_id',
    'academic_year_id',
    'semester_id',
    'grading_category_id',
    'offering_id',
    'name',
    'name_ar',
    'description',
    'description_ar',
    'due_date',
    'max_score',
    'weight',
    'is_published',
])]
class Assessment extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function gradingCategory(): BelongsTo
    {
        return $this->belongsTo(GradingCategory::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'max_score' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }
}
