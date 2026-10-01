<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property-read School|null $school
 * @property-read Student|null $student
 */
#[Fillable([
    'school_id',
    'student_id',
    'invoice_number',
    'issue_date',
    'due_date',
    'subtotal',
    'tax_amount',
    'tax_rate',
    'discount_amount',
    'total_amount',
    'amount_paid',
    'balance_due',
    'currency',
    'status',
    'notes',
    'notes_ar',
    'vat_amount',
    'vat_rate',
    'qr_code_data',
    'sent_at',
    'delivery_channels',
    'reminder_sent_at',
    'paid_at',
])]
#[UseFactory(InvoiceFactory::class)]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToSchool, HasFactory, SoftDeletes;

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * The amount still owed, never negative.
     */
    public function outstanding(): float
    {
        return max(0.0, (float) $this->balance_due);
    }

    public function isPaid(): bool
    {
        return $this->outstanding() <= 0.0;
    }

    /**
     * @return Collection<int, GuardianRelationship>
     */
    public function guardianRelationships(): Collection
    {
        return GuardianRelationship::query()
            ->where('school_id', $this->school_id)
            ->where('student_id', $this->student_id)
            ->with('guardian')
            ->get();
    }

    /**
     * Guardians who should receive this invoice: the financial guardian first,
     * falling back to every linked guardian.
     *
     * Each guardian carries an `is_financial_guardian` attribute so callers do
     * not have to re-read the pivot.
     *
     * @return Collection<int, Guardian>
     */
    public function guardians(): Collection
    {
        $relationships = $this->guardianRelationships();

        if ($relationships->isEmpty()) {
            return collect();
        }

        $financial = $relationships->where('is_financial_guardian', true);
        $selected = $financial->isNotEmpty() ? $financial : $relationships;

        return $selected
            ->map(function (GuardianRelationship $relationship): ?Guardian {
                $guardian = $relationship->guardian;

                return $guardian?->setAttribute('is_financial_guardian', (bool) $relationship->is_financial_guardian);
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'delivery_channels' => 'array',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'balance_due' => 'decimal:4',
        ];
    }
}
