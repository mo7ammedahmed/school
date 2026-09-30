<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use App\Domain\Assessment\Models\Exam;
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
    'name_ar',
    'name_en',
    'code',
    'room_type',
    'capacity',
    'description',
    'description_ar',
])]
#[Appends([
    'name',
])]
class Room extends Model
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

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
