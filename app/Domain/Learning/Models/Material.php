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
    'file_path',
    'file_type',
    'file_size',
    'is_published',
])]
#[Appends(['subject', 'section', 'uploaded_at'])]
class Material extends Model
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

    protected function uploadedAt(): Attribute
    {
        return Attribute::make(get: fn () => $this->created_at?->toDateTimeString());
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
