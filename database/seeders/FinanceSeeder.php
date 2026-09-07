<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Schools\Models\School;
use App\Domain\People\Models\Student;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Finance\Models\FeeType;
use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeAssignment;
use App\Domain\Finance\Models\Discount;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\InvoiceLine;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Models\GatewayTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the finance domain with coherent money data:
 * decimal(19,4) amounts, VAT (ZATCA-style), invoice lines,
 * payments with allocations, refunds and gateway transactions.
 */
class FinanceSeeder extends Seeder
{
    private const VAT_RATE = 0.15;

    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->firstOrFail();
        $year = AcademicYear::where('school_id', $school->id)->where('is_current', true)->first();
        $gradeLevels = GradeLevel::where('school_id', $school->id)->orderBy('level')->get();
        $students = Student::where('school_id', $school->id)->orderBy('id')->get();

        if ($students->isEmpty()) {
            return;
        }

        // ---- Fee types ----
        $feeTypes = [];
        foreach ([
            ['name' => 'Tuition Fee', 'code' => 'TUITION', 'description' => 'Annual tuition', 'is_recurring' => true, 'frequency' => 'annually'],
            ['name' => 'Transport Fee', 'code' => 'TRANSPORT', 'description' => 'Bus transportation', 'is_recurring' => true, 'frequency' => 'annually'],
            ['name' => 'Exam Fee', 'code' => 'EXAM', 'description' => 'Examination fee', 'is_recurring' => false, 'frequency' => null],
        ] as $t) {
            $feeTypes[$t['code']] = FeeType::firstOrCreate(
                ['school_id' => $school->id, 'code' => $t['code']],
                $t + ['school_id' => $school->id]
            );
        }

        // ---- Fee structures per grade level ----
        foreach ($feeTypes as $code => $feeType) {
            $amounts = ['TUITION' => 18000, 'TRANSPORT' => 3600, 'EXAM' => 500];
            foreach ($gradeLevels as $grade) {
                FeeStructure::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'fee_type_id' => $feeType->id,
                        'grade_level_id' => $grade->id,
                    ],
                    [
                        'academic_year_id' => $year?->id,
                        'amount' => $amounts[$code] ?? 1000,
                        'description' => $feeType->name.' for '.$grade->name,
                    ]
                );
            }
        }

        // ---- Fee assignments for first students ----
        $tuitionStructure = FeeStructure::where('school_id', $school->id)
            ->where('fee_type_id', $feeTypes['TUITION']->id)
            ->first();

        foreach ($students->take(10) as $student) {
            if (!$tuitionStructure) {
                break;
            }
            FeeAssignment::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'fee_structure_id' => $tuitionStructure->id,
                    'student_id' => $student->id,
                ],
                [
                    'amount' => $tuitionStructure->amount,
                    'discount_amount' => 0,
                ]
            );
        }

        // ---- Discount ----
        Discount::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Sibling Discount'],
            [
                'type' => 'percentage',
                'value' => 10,
                'start_date' => now()->startOfYear()->format('Y-m-d'),
                'end_date' => now()->endOfYear()->format('Y-m-d'),
                'is_active' => true,
            ]
        );

        // ---- Invoices: one per student, in varied lifecycle states ----
        $statuses = ['issued', 'partially_paid', 'paid', 'draft', 'issued'];
        $seq = 1;

        foreach ($students->take(10) as $index => $student) {
            $status = $statuses[$index % count($statuses)];
            $tuition = 18000.0;
            $exam = 500.0;
            $subtotal = $tuition + $exam;
            $discountAmount = $index % 4 === 0 ? round($subtotal * 0.10, 4) : 0.0;
            $taxable = $subtotal - $discountAmount;
            $vat = round($taxable * self::VAT_RATE, 4);
            $total = round($taxable + $vat, 4);

            $paid = match ($status) {
                'paid' => $total,
                'partially_paid' => round($total / 2, 4),
                default => 0.0,
            };

            $invoice = Invoice::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'invoice_number' => 'ANS-INV-'.now()->format('Y').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ],
                [
                    'student_id' => $student->id,
                    'issue_date' => now()->subDays(30 - $index)->format('Y-m-d'),
                    'due_date' => now()->addDays($index + 15)->format('Y-m-d'),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $vat,
                    'tax_rate' => self::VAT_RATE,
                    'total_amount' => $total,
                    'amount_paid' => $paid,
                    'balance_due' => round($total - $paid, 4),
                    'currency' => 'SAR',
                    'status' => $status,
                    'notes' => 'Annual fees for '.$student->first_name.' '.$student->last_name,
                    'vat_amount' => (string) $vat,
                    'vat_rate' => (string) self::VAT_RATE,
                    'qr_code_data' => $status === 'draft' ? null : $this->zatcaQrPayload(
                        'Al Noor School', $student->first_name.' '.$student->last_name, $total, $vat
                    ),
                ]
            );
            $seq++;

            // Invoice lines
            InvoiceLine::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'description' => 'Tuition Fee (annual)',
                ],
                [
                    'school_id' => $school->id,
                    'quantity' => 1,
                    'unit_price' => $tuition,
                    'amount' => $tuition,
                    'discount_amount' => $discountAmount * ($tuition / $subtotal),
                    'tax_amount' => $vat * ($tuition / $subtotal),
                    'tax_rate' => (string) self::VAT_RATE,
                ]
            );
            InvoiceLine::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'description' => 'Exam Fee',
                ],
                [
                    'school_id' => $school->id,
                    'quantity' => 1,
                    'unit_price' => $exam,
                    'amount' => $exam,
                    'discount_amount' => $discountAmount * ($exam / $subtotal),
                    'tax_amount' => $vat * ($exam / $subtotal),
                    'tax_rate' => (string) self::VAT_RATE,
                ]
            );

            // ---- Payments + allocations ----
            if ($status === 'paid' || $status === 'partially_paid') {
                $method = $index % 2 === 0 ? 'card' : 'bank_transfer';
                $isGateway = $method === 'card';

                $payment = Payment::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'payment_number' => 'ANS-PAY-'.now()->format('Y').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                    ],
                    [
                        'student_id' => $student->id,
                        'invoice_id' => $invoice->id,
                        'payment_date' => now()->subDays(15 - ($index % 10))->format('Y-m-d'),
                        'amount' => $paid,
                        'currency' => 'SAR',
                        'payment_method' => $method,
                        'status' => 'completed',
                        'reference_number' => 'REF-'.strtoupper(Str::random(8)),
                        'notes' => $isGateway ? 'Paid online via Moyasar' : 'Bank transfer reconciliation',
                    ]
                );

                PaymentAllocation::firstOrCreate(
                    [
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                    ],
                    [
                        'school_id' => $school->id,
                        'amount' => $paid,
                    ]
                );

                if ($isGateway) {
                    GatewayTransaction::firstOrCreate(
                        ['gateway_transaction_id' => 'MOY-'.strtoupper(Str::random(12))],
                        [
                            'school_id' => $school->id,
                            'payment_id' => $payment->id,
                            'gateway' => 'moyasar',
                            'status' => 'completed',
                            'currency' => 'SAR',
                            'amount' => $paid,
                            'payload' => null,
                            'response' => json_encode(['source' => 'seeder', 'settled_at' => now()->toIso8601String()]),
                        ]
                    );
                }
            }

            // ---- Refund for one overpaid scenario ----
            if ($index === 2) {
                Refund::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                    ],
                    [
                        'amount' => 250.0,
                        'currency' => 'SAR',
                        'reason' => 'Transport fee credit for service interruption',
                        'status' => 'approved',
                        'processed_at' => now()->subDays(3),
                    ]
                );
            }
        }

        // One explicitly pending review payment for the reconciliation demo
        $reviewStudent = $students->first();
        if ($reviewStudent) {
            $invoice = Invoice::where('school_id', $school->id)->where('student_id', $reviewStudent->id)->first();
            if ($invoice) {
                Payment::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'payment_number' => 'ANS-PAY-REVIEW-001',
                    ],
                    [
                        'student_id' => $reviewStudent->id,
                        'invoice_id' => $invoice->id,
                        'payment_date' => now()->format('Y-m-d'),
                        'amount' => 100.0,
                        'currency' => 'SAR',
                        'payment_method' => 'card',
                        'status' => 'review',
                        'reference_number' => 'REF-REVIEW001',
                        'notes' => 'Amount mismatch from gateway — needs manual reconciliation.',
                    ]
                );
            }
        }
    }

    /**
     * ZATCA Phase-1 (simplified) QR payload: base64 TLV of seller, buyer,
     * timestamp, total and VAT. Demo values only — production requires the
     * real ZATCA-certified implementation.
     */
    private function zatcaQrPayload(string $seller, string $buyer, float $total, float $vat): string
    {
        $tlv = function (int $tag, string $value): string {
            return chr($tag).chr(strlen($value)).$value;
        };

        $payload = $tlv(1, $seller)
            .$tlv(2, $buyer)
            .$tlv(3, now()->toIso8601String())
            .$tlv(4, number_format($total, 2, '.', ''))
            .$tlv(5, number_format($vat, 2, '.', ''));

        return base64_encode($payload);
    }
}
