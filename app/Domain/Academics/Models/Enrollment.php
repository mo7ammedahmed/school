<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\People\Models\Student;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Section;
use App\Domain\Schools\Models\School;
use App\Domain\Finance\Models\FeeAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'student_id',
    'academic_year_id',
    'section_id',
    'enrollment_date',
    'withdrawal_date',
    'status',
    'notes',
])]
class Enrollment extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function feeAssignments(): HasMany
    {
        return $this->hasMany(FeeAssignment::class);
    }
    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'withdrawal_date' => 'date',
        ];
    }
}
