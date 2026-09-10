<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'offering_id',
    'title',
    'description',
    'questions',
    'time_limit_minutes',
    'max_score',
    'is_published',
])]
class Quiz extends Model
{
    use SoftDeletes;

    /** Legacy presentation aliases kept for the admin pages (source fields live on the offering + quiz). */
    protected $appends = ['subject', 'section', 'total_marks', 'passing_marks', 'duration_minutes', 'status'];

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

    public function getDurationMinutesAttribute(): ?int
    {
        return $this->time_limit_minutes;
    }

    public function getStatusAttribute(): string
    {
        return $this->is_published ? 'published' : 'draft';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'max_score' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }
}
