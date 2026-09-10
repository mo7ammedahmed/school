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
    'instructions',
    'due_date',
    'max_score',
    'allow_late_submission',
    'is_published',
])]
class Assignment extends Model
{
    use SoftDeletes;

    /**
     * Subject/section surface the owning offering; marks/publish state are exposed under
     * the legacy names the admin pages were written against.
     */
    protected $appends = ['subject', 'section', 'total_marks', 'status'];

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
