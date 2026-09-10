<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'fee_structure_id',
    'student_id',
    'amount',
    'discount_amount',
    'notes',
])]
class FeeAssignment extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
        ];
    }
}
