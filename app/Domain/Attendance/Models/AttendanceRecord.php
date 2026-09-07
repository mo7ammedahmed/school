<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'attendance_session_id',
    'student_id',
    'status',
    'notes',
    'recorded_by',
])]
class AttendanceRecord extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Presentation aliases kept for the v1 admin pages. */
    protected $appends = ['date', 'remarks'];

    public function getDateAttribute()
    {
        return $this->attendanceSession?->session_date?->toDateString();
    }

    public function getRemarksAttribute(): ?string
    {
        return $this->notes;
    }

    protected function casts(): array
    {
        return [];
    }
}
