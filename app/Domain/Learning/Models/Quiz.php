<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'offering_id',
    'title',
    'title_ar',
    'description',
    'description_ar',
    'questions',
    'time_limit_minutes',
    'max_score',
    'is_published',
])]
#[Appends(['subject', 'section', 'total_marks', 'passing_marks', 'duration_minutes', 'status'])]
class Quiz extends Model
{
    use SoftDeletes;

    protected function subject(): Attribute
    {
        return Attribute::make(get: fn () => $this->offering?->subject);
    }

    protected function section(): Attribute
    {
        return Attribute::make(get: fn () => $this->offering?->section);
    }

    protected function totalMarks(): Attribute
    {
        return Attribute::make(get: fn () => $this->max_score !== null ? (float) $this->max_score : null);
    }

    protected function passingMarks(): Attribute
    {
        return Attribute::make(get: fn () => $this->max_score !== null ? (float) $this->max_score * 0.5 : null);
    }

    protected function durationMinutes(): Attribute
    {
        return Attribute::make(get: fn () => $this->time_limit_minutes);
    }

    protected function status(): Attribute
    {
        return Attribute::make(get: fn () => $this->is_published ? 'published' : 'draft');
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
