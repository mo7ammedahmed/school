<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use App\Domain\Assessment\Models\Exam;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'code',
    'room_type',
    'capacity',
    'description',
])]
#[Appends([
    'name',
])]
class Room extends Model
{
    use SoftDeletes;

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
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
