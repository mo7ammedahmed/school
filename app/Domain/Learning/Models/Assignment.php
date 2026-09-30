<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `subject` and `section` are appended accessors that walk to the offering.
 *
 * @property-read Subject|null $subject
 * @property-read Section|null $section
 * @property-read Offering|null $offering
 */
#[Fillable([
    'school_id',
    'offering_id',
    'title',
    'title_ar',
    'description',
    'description_ar',
    'instructions',
    'instructions_ar',
    'due_date',
    'max_score',
    'allow_late_submission',
    'is_published',
])]
#[Appends(['subject', 'section', 'total_marks', 'status'])]
class Assignment extends Model
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

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'max_score' => 'decimal:2',
            'allow_late_submission' => 'boolean',
            'is_published' => 'boolean',
        ];
    }
}
