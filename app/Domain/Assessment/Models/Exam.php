<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

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
    'is_published',
])]
class Exam extends Model
{
    use SoftDeletes;

    /** Legacy presentation aliases kept for the admin pages (source fields live on the offering + exam). */
    protected $appends = ['subject', 'section', 'total_marks', 'passing_marks', 'status'];

    public function getSubjectAttribute()
    {
        return $this->offering?->subject;
    }

    public function getSectionAttribute()
    {
        return $this->offering?->section;
    }

    public function getTotalMarksAttribute(): ?float
    {
        return $this->max_score !== null ? (float) $this->max_score : null;
    }

    public function getPassingMarksAttribute(): ?float
    {
        return $this->max_score !== null ? (float) $this->max_score * 0.5 : null;
    }

    public function getStatusAttribute(): string
    {
        return $this->is_published ? 'published' : 'draft';
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
            'is_published' => 'boolean',
        ];
    }
}
