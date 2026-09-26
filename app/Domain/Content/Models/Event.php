<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
#[Appends(['event_date', 'start_time', 'end_time', 'target_audience', 'is_active'])]
class Event extends Model
{
    use SoftDeletes;
    protected function eventDate(): Attribute
    {
        return Attribute::make(get: fn() => $this->start_date?->toDateString());
    }
    protected function startTime(): Attribute
    {
        return Attribute::make(get: fn() => $this->start_date?->format('H:i'));
    }
    protected function endTime(): Attribute
    {
        return Attribute::make(get: fn() => $this->end_date?->format('H:i'));
    }
    protected function targetAudience(): Attribute
    {
        return Attribute::make(get: fn() => $this->event_type ?: 'all');
    }
    protected function isActive(): Attribute
    {
        return Attribute::make(get: fn() => (bool) $this->is_published);
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
