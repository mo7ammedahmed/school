<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    'bio',
    'specialization',
    'avatar_path',
    'metadata',
])]
class TeacherProfile extends Model
{
    use SoftDeletes;

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
