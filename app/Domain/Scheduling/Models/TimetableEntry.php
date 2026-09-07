<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Schools\Models\School;
use App\Domain\Academics\Models\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'academic_year_id',
    'semester_id',
    'offering_id',
    'room_id',
    'teacher_id',
    'section_id',
    'day_of_week',
    'start_time',
    'end_time',
    'is_published',
])]
class TimetableEntry extends Model
{
    use SoftDeletes;

    /** Subject is surfaced through the owning offering (section/teacher/room live directly on the entry). */
    protected $appends = ['subject'];

    public function getSubjectAttribute()
    {
        return $this->offering?->subject;
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
