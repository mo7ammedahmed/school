<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Academics\Models\Enrollment;
use App\Domain\Academics\Models\Offering;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Schools\Models\School;
use App\Domain\Attendance\Models\AttendanceSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'academic_year_id',
    'grade_level_id',
    'name',
    'code',
    'homeroom_teacher_id',
    'capacity',
    'current_count',
    'notes',
])]
class Section extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'homeroom_teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    /**
     * Students currently enrolled in this section (active enrollments only).
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'enrollments',
            'section_id',
            'student_id'
        )->withPivot(['academic_year_id', 'enrollment_date', 'status']);
    }
}
