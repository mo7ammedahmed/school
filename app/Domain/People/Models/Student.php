<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\Academics\Models\Enrollment;
use App\Domain\Assessment\Models\AssessmentScore;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Learning\Models\Submission;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'user_id',
    'first_name',
    'last_name',
    'student_id_number',
    'date_of_birth',
    'gender',
    'nationality',
    'national_id_number',
    'passport_number',
    'address',
    'phone',
    'email',
    'enrollment_date',
    'status',
    'medical_notes',
    'metadata',
])]
#[UseFactory(StudentFactory::class)]
class Student extends Model
{
    use HasFactory, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Guardians linked through the guardian_relationships pivot.
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_relationships', 'student_id', 'guardian_id')
            ->withPivot(['is_primary', 'is_financial_guardian', 'can_pickup'])
            ->withTimestamps();
    }

    /**
     * Primary guardian (or first linked guardian) exposed for convenience.
     */
    protected function guardian(): Attribute
    {
        return Attribute::get(fn (): ?Guardian => $this->guardians->sortByDesc('pivot.is_primary')->first());
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'national_id_number' => 'encrypted',
            'medical_notes' => 'encrypted',
            'metadata' => 'array',
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
        ];
    }
}
