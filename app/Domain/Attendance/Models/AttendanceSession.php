<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'academic_year_id',
    'semester_id',
    'offering_id',
    'section_id',
    'teacher_id',
    'session_date',
    'start_time',
    'end_time',
    'status',
    'is_finalized',
])]
#[Appends(['subject'])]
class AttendanceSession extends Model
{
    use SoftDeletes;
    protected function subject(): Attribute
    {
        return Attribute::make(get: fn() => $this->offering?->subject);
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

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    protected function casts(): array
    {
        return [
            'session_date' => 'date:Y-m-d',
            'is_finalized' => 'boolean',
        ];
    }
}
