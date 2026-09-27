<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'attendance_session_id',
    'student_id',
    'status',
    'notes',
    'notes_ar',
    'recorded_by',
])]
#[Appends(['date', 'remarks'])]
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

    protected function date(): Attribute
    {
        return Attribute::make(get: fn () => $this->attendanceSession?->session_date?->toDateString());
    }

    protected function remarks(): Attribute
    {
        return Attribute::make(get: fn () => $this->notes);
    }

    protected function casts(): array
    {
        return [];
    }
}
