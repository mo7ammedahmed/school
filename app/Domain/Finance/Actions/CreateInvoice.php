<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\Invoice;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateInvoice
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Student $student, array $lineData, ?string $dueDate = null, ?string $notes = null): Invoice
    {
        if ($student->school_id !== $this->school->id) {
            throw new Exception('Student does not belong to this school.');
        }

        // Accept either a single line (associative array) or a list of lines.
        if (! array_is_list($lineData) && isset($lineData['description'])) {
            $lineData = [$lineData];
        }

        if ($lineData === []) {
            throw new Exception('At least one invoice line is required.');
        }

        $subtotal = 0.0;
        $lines = [];

        foreach ($lineData as $line) {
            if (empty($line['description']) || !isset($line['amount']) || $line['amount'] <= 0) {
                throw new Exception('Each invoice line must have a description and a positive amount.');
            }

            $quantity = (int) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? $line['amount']);
            $amount = (float) $line['amount'];
            $subtotal += $amount;
            $lines[] = [
                'description' => $line['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $quantity,
            ];
        }

        $taxRate = $lineData[0]['tax_rate'] ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        return DB::transaction(function () use ($student, $subtotal, $taxRate, $taxAmount, $totalAmount, $dueDate, $notes, $lines) {
            $invoice = Invoice::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'balance_due' => $totalAmount,
                'currency' => $this->school->currency ?? 'USD',
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $invoice->lines()->create(array_merge($line, [
                    'school_id' => $this->school->id,
                ]));
            }

            return $invoice->load('lines');
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Y') . '-';
        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            return $prefix . str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
        }

        return $prefix . str_pad('1', 6, '0', STR_PAD_LEFT);
    }
}
