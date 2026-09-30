<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `name` is an accessor over `name_en`/`name_ar`, not a column.
 *
 * @property string $name
 */
#[Fillable([
    'school_id',
    'name_en',
    'name_ar',
    'code',
    'start_time',
    'end_time',
    'sort_order',
    'is_break',
])]
#[Appends([
    'name',
])]
class Period extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected function name(): Attribute
    {
        return Attribute::make(get: function () {
            $locale = app()->getLocale();

            return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    /**
     * 'HH:MM:SS' storage trimmed to 'HH:MM' for display.
     */
    public function startsAt(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endsAt(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_break' => 'boolean',
        ];
    }
}
