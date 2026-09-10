<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    'vat_amount',
    'vat_rate',
    'qr_code_data',
])]
#[UseFactory(InvoiceFactory::class)]
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
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
