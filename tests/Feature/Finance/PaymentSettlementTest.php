<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Mail\InvoiceMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_a_gateway_webhook_marks_the_invoice_paid_and_receipts_the_guardian(): void
    {
        [$invoice, $payment, $guardian] = $this->invoiceWithPendingPayment();

        $this->postJson('/webhooks/payments/moyasar', $this->payload($payment, 115000))
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $invoice->refresh();
        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->balance_due, 0.001);
        $this->assertEqualsWithDelta(1150.0, (float) $invoice->amount_paid, 0.001);
        $this->assertNotNull($invoice->paid_at);

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->kind === InvoiceMail::KIND_RECEIPT
            && $mail->invoice->id === $invoice->id
            && $mail->hasTo($guardian->email));
    }

    public function test_a_partial_webhook_payment_leaves_the_invoice_partially_paid(): void
    {
        [$invoice, $payment] = $this->invoiceWithPendingPayment();

        $payment->update(['amount' => 500]);

        $this->postJson('/webhooks/payments/moyasar', $this->payload($payment, 50000))->assertOk();

        $invoice->refresh();

        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEqualsWithDelta(650.0, (float) $invoice->balance_due, 0.001);
        $this->assertNull($invoice->paid_at);
    }

    public function test_a_duplicate_webhook_is_ignored(): void
    {
        [$invoice, $payment] = $this->invoiceWithPendingPayment();

        $payload = $this->payload($payment, 115000);

        $this->postJson('/webhooks/payments/moyasar', $payload)->assertOk();
        $this->postJson('/webhooks/payments/moyasar', $payload)->assertOk();

        $invoice->refresh();

        $this->assertSame(1, WebhookEvent::count());
        $this->assertEqualsWithDelta(1150.0, (float) $invoice->amount_paid, 0.001, 'the invoice must not be paid twice');
        Mail::assertSentCount(1);
    }

    public function test_a_second_payment_settles_an_invoice_that_was_partially_paid(): void
    {
        [$invoice, $payment] = $this->invoiceWithPendingPayment();

        $payment->update(['amount' => 500]);
        $this->postJson('/webhooks/payments/moyasar', $this->payload($payment, 50000))->assertOk();

        $second = Payment::create([
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-SECOND',
            'payment_date' => now()->toDateString(),
            'amount' => 650,
            'currency' => 'SAR',
            'payment_method' => 'moyasar',
            'status' => 'pending',
        ]);

        $this->postJson('/webhooks/payments/moyasar', $this->payload($second, 65000))->assertOk();

        $invoice->refresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->balance_due, 0.001);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_a_webhook_for_an_unknown_payment_does_not_error(): void
    {
        $response = $this->postJson('/webhooks/payments/moyasar', [
            'id' => 'evt_unknown_1',
            'type' => 'payment.paid',
            'amount' => 1000,
            'metadata' => ['payment_id' => 999999],
        ]);

        $response->assertOk();
        $this->assertSame(0, WebhookEvent::where('status', 'completed')->count());
    }

    public function test_confirming_an_offline_payment_settles_the_invoice(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $student = Student::factory()->create(['school_id' => $school->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'issued',
            'subtotal' => 1000,
            'total_amount' => 1150,
            'balance_due' => 1150,
            'amount_paid' => 0,
        ]);

        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-OFFLINE',
            'payment_date' => now()->toDateString(),
            'amount' => 1150,
            'currency' => 'SAR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->post("/finance/payments/{$payment->id}/confirm", ['reference_number' => 'BANK-42'])
            ->assertRedirect('/finance/payments/offline');

        $invoice->refresh();
        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('BANK-42', $payment->reference_number);
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->balance_due, 0.001);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    public function test_a_payment_from_another_school_cannot_be_confirmed(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $student = Student::factory()->create(['school_id' => $other->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $other->id,
            'student_id' => $student->id,
            'status' => 'issued',
        ]);

        $payment = Payment::create([
            'school_id' => $other->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-OTHER',
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->post("/finance/payments/{$payment->id}/confirm")->assertForbidden();
    }

    /**
     * @return array{0: Invoice, 1: Payment, 2: Guardian}
     */
    private function invoiceWithPendingPayment(): array
    {
        $school = School::factory()->create();

        $student = Student::factory()->create(['school_id' => $school->id, 'email' => null]);

        $guardian = Guardian::create([
            'school_id' => $school->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Amina',
            'last_name' => 'Hassan',
            'email' => 'amina@example.test',
        ]);

        GuardianRelationship::create([
            'school_id' => $school->id,
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'is_financial_guardian' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'issued',
            'subtotal' => 1000,
            'total_amount' => 1150,
            'balance_due' => 1150,
            'amount_paid' => 0,
            'currency' => 'SAR',
        ]);

        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-'.uniqid(),
            'payment_date' => now()->toDateString(),
            'amount' => 1150,
            'currency' => 'SAR',
            'payment_method' => 'moyasar',
            'status' => 'pending',
        ]);

        GatewayTransaction::create([
            'school_id' => $school->id,
            'payment_id' => $payment->id,
            'gateway' => 'moyasar',
            'gateway_transaction_id' => 'trx-'.$payment->id,
            'status' => 'pending',
            'amount' => 1150,
        ]);

        return [$invoice, $payment, $guardian];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, int $minorAmount): array
    {
        return [
            'id' => 'evt_'.$payment->id,
            'type' => 'payment.paid',
            'amount' => $minorAmount,
            'status' => 'paid',
            'metadata' => [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'invoice_id' => $payment->invoice_id,
                'school_id' => $payment->school_id,
            ],
        ];
    }

    private function actingAsSchoolUser(School $school): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
