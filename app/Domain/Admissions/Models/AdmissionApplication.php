<?php

declare(strict_types=1);

namespace App\Domain\Admissions\Models;

use App\Models\User;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'admission_period_id',
    'reference',
    'status',
    'guardian_first_name',
    'guardian_last_name',
    'guardian_email',
    'guardian_phone',
    'guardian_relationship',
    'guardian_national_id',
    'student_first_name',
    'student_last_name',
    'student_date_of_birth',
    'student_gender',
    'student_nationality',
    'grade_applying',
    'student_notes',
    'previous_school_name',
    'previous_school_last_grade',
    'previous_school_year_completed',
    'documents',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
    'converted_student_id',
    'submitted_at',
    'assigned_to',
    'priority',
    'internal_notes',
])]
class AdmissionApplication extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'approved',
        'rejected',
        'converted',
        'withdrawn',
    ];

    public const PRIORITY_LEVELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class, 'admission_period_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdmissionApplicationEvent::class);
    }

    protected function casts(): array
    {
        return [
            'guardian_national_id' => 'encrypted',
            'documents' => 'array',
            'student_date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'assigned_to' => 'integer',
            'priority' => 'string',
        ];
    }

    public function isReviewable(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    public function isDecidable(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    public function isConvertible(): bool
    {
        return $this->status === 'approved' && ! $this->converted_student_id;
    }
}
