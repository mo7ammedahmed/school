<?php

declare(strict_types=1);

namespace App\Domain\Academics\Models;

use App\Domain\Finance\Models\FeeAssignment;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Student|null $student
 * @property-read Section|null $section
 */
#[Fillable([
    'school_id',
    'student_id',
    'academic_year_id',
    'section_id',
    'enrollment_date',
    'withdrawal_date',
    'status',
    'notes',
    'notes_ar',
])]
class Enrollment extends Model
{
    use BelongsToSchool, SoftDeletes;

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
