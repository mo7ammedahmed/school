<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Database\Factories\Domain\People\Models\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'user_id',
    'employee_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'hire_date',
    'qualification',
    'qualification_ar',
    'bio',
    'bio_ar',
    'specialization',
    'specialization_ar',
    'avatar_path',
    'metadata',
])]
#[UseFactory(TeacherFactory::class)]
class TeacherProfile extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class, 'teacher_id');
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'teacher_id');
    }

    /**
     * The subjects this teacher teaches, which is what an offering records.
     *
     * There is no teacher/subject pivot table: a teacher is attached to a
     * subject by the offerings assigned to them, so that is the relation. The
     * public teachers page called `with('subjects')` when no such relation
     * existed anywhere, which threw instead of rendering the page at all.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'offerings', 'teacher_id', 'subject_id')->distinct();
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'teacher_id');
    }

    public function homeroomSections(): HasMany
    {
        return $this->hasMany(Section::class, 'homeroom_teacher_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'hire_date' => 'date',
        ];
    }
}
