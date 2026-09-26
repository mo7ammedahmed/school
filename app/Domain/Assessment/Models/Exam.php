<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Semester;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Offering|null $offering
 */
#[Fillable([
    'school_id',
    'academic_year_id',
    'semester_id',
    'offering_id',
    'name',
    'description',
    'exam_date',
    'start_time',
    'end_time',
    'room_id',
    'max_score',
    'questions',
    'is_published',
])]
#[Appends(['subject', 'section', 'total_marks', 'passing_marks', 'status'])]
class Exam extends Model
{
    use SoftDeletes;
    protected function subject(): Attribute
    {
        return Attribute::make(get: fn() => $this->offering?->subject);
    }
    protected function section(): Attribute
    {
        return Attribute::make(get: fn() => $this->offering?->section);
    }
    protected function totalMarks(): Attribute
    {
        return Attribute::make(get: fn() => $this->max_score !== null ? (float) $this->max_score : null);
    }
    protected function passingMarks(): Attribute
    {
        return Attribute::make(get: fn() => $this->max_score !== null ? (float) $this->max_score * 0.5 : null);
    }
    protected function status(): Attribute
    {
        return Attribute::make(get: fn() => $this->is_published ? 'published' : 'draft');
    }

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

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    protected function casts(): array
    {
        return [
            'exam_date' => 'date:Y-m-d',
            'max_score' => 'decimal:2',
            'questions' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
