<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use App\Domain\Scheduling\Enums\CalendarDayType;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An author-editable calendar entry: holidays, closures, term boundaries and
 * staff days. Everything derivable from other records is aggregated by
 * App\Domain\Scheduling\Services\AcademicCalendar instead.
 */
#[Fillable([
    'school_id',
    'academic_year_id',
    'semester_id',
    'date',
    'end_date',
    'type',
    'title',
    'description',
    'is_instructional',
    'created_by',
])]
class CalendarDay extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (CalendarDay $day): void {
            if ($day->is_instructional === null) {
                $day->is_instructional = $day->type->isInstructionalByDefault();
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Last day covered by this entry (single days have no end date).
     */
    public function lastDay(): string
    {
        return ($this->end_date ?? $this->date)?->toDateString();
    }

    public function spansMultipleDays(): bool
    {
        return $this->end_date !== null && ! $this->end_date->isSameDay($this->date);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'end_date' => 'date',
            'type' => CalendarDayType::class,
            'is_instructional' => 'boolean',
        ];
    }
}
