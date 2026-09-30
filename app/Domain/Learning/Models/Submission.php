<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Assignment|null $assignment
 * @property-read Student|null $student
 */
#[Fillable([
    'school_id',
    'assignment_id',
    'student_id',
    'content',
    'file_path',
    'file_type',
    'file_size',
    'score',
    'feedback',
    'feedback_ar',
    'graded_by',
    'graded_at',
    'submitted_at',
])]
class Submission extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
