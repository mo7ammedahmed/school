<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'title',
    'slug',
    'description',
    'start_date',
    'end_date',
    'location',
    'event_type',
    'featured_image_path',
    'is_published',
])]
class Event extends Model
{
    use SoftDeletes;

    /** Legacy admin-page aliases (the v2 schema stores single datetime ranges + an event type). */
    protected $appends = ['event_date', 'start_time', 'end_time', 'target_audience', 'is_active'];

    public function getEventDateAttribute()
    {
        return $this->start_date?->toDateString();
    }

    public function getStartTimeAttribute(): ?string
    {
        return $this->start_date?->format('H:i');
    }

    public function getEndTimeAttribute(): ?string
    {
        return $this->end_date?->format('H:i');
    }

    public function getTargetAudienceAttribute(): string
    {
        return $this->event_type ?: 'all';
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->is_published;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
