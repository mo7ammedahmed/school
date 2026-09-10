<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'guardian_id',
    'student_id',
    'is_primary',
    'is_financial_guardian',
    'can_pickup',
    'notes',
])]
class GuardianRelationship extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_financial_guardian' => 'boolean',
            'can_pickup' => 'boolean',
        ];
    }
}
