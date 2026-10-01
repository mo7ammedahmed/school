<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentAllocation;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A school's most audit-worthy event is money arriving: "who marked this invoice
 * paid, and on what basis?" is the first question asked when a figure is
 * disputed, and the audit log is where the answer is supposed to be.
 *
 * Two gaps stood in the way of an answer, and neither was visible from the
 * screen that displays the log.
 *
 * The gateway path has no user, because it is an unauthenticated webhook — and
 * that is correct. But it also wrote no IP, no user agent, and no reference to
 * the gateway transaction it was reacting to, so the entry recorded *that*
 * something settled and nothing about *what*. It was not evidence of anything.
 *
 * And the manual path had the same hole. `ip_address` and `user_agent` are
 * columns in `audit_logs` that no write site in the entire application ever
 * populated, so every audit row the system had ever written had them null.
 */
class SettlementIsAuditableTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Settlement does not take the delivery's word for it: it asks the
        // gateway what actually happened to the charge. Without a fake here the
        // test would reach the real provider and the settlement would answer
        // "retry", which is a different code path from the one under test.
        //
        // `verifyPayment()` returns the whole response body as `data`, so the
        // status sits at the top level. Nesting it — as `success`/`data` looks
        // like it should be — makes settlement read the payment as "unknown".
        Http::fake(function (ClientRequest $request) {
            return Http::response([
                'id' => basename((string) parse_url($request->url(), PHP_URL_PATH)),
                'status' => 'paid',
                'amount' => 50000,
                'currency' => 'SAR',
            ]);
        });

        $this->school = School::factory()->create();

        // The tenant scope hides school-owned rows from any query made before a
        // request pins a school, and settlement reads the invoice relation.
        $this->app->make(TenantContext::class)->set($this->school->id);

        GatewaySettings::for($this->school)->save([
            'gateway' => 'moyasar',
            'enabled' => true,
            'public_key' => 'pk_test',
            'secret_key' => 'sk_test',
            'webhook_secret' => 'whsec_test_secret',
        ]);
    }

    public function test_a_gateway_settlement_records_the_transaction_it_reacted_to(): void
    {
        [$payment, $transaction] = $this->paymentAndTransaction();

        $this->deliver($payment, $transaction);

        $log = $this->onlyLog();

        $this->assertSame(
            $transaction->gateway_transaction_id,
            $log->new_values['gateway_transaction_id'] ?? null,
            'The settlement entry does not name the gateway transaction that caused it, so it cannot be '
            .'traced back to the provider event or reconciled against the gateway\'s own records.',
        );
    }

    public function test_a_gateway_settlement_records_which_gateway_settled_it(): void
    {
        [$payment, $transaction] = $this->paymentAndTransaction();

        $this->deliver($payment, $transaction);

        $this->assertSame(
            'moyasar',
            $this->onlyLog()->new_values['gateway'] ?? null,
            'The entry does not say which provider settled the payment, so two providers in the same school '
            .'are indistinguishable in the audit log.',
        );
    }

    public function test_a_gateway_settlement_records_where_it_came_from(): void
    {
        [$payment, $transaction] = $this->paymentAndTransaction();

        // Unauthenticated, exactly as the webhook is. There is no user, and the
        // absence of one must not become the absence of all provenance — or an
        // automated movement of money is untraceable.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/webhooks/payments/moyasar', $this->payload($payment, $transaction))
            ->assertOk();

        $log = $this->onlyLog();

        $this->assertNull(
            $log->user_id,
            'The webhook is not authenticated, so an entry naming a user here would be a fabrication.',
        );
        $this->assertSame(
            '203.0.113.9',
            $log->ip_address,
            'The entry has no source address, so an automated settlement cannot be traced to anything.',
        );
    }

    public function test_a_manual_settlement_records_the_user_and_where_they_were(): void
    {
        [$payment] = $this->paymentAndTransaction();

        $user = $this->paymentClerk();
        $this->actingAs($user);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4', 'HTTP_USER_AGENT' => 'Mozilla/5.0 test'])
            ->post('/finance/payments/'.$payment->id.'/confirm', ['reference_number' => 'TRF-9911'])
            ->assertRedirect();

        $log = $this->onlyLog();

        $this->assertSame(
            $user->id,
            $log->user_id,
            'A human confirmed this payment and the entry does not name them.',
        );
        $this->assertSame(
            '198.51.100.4',
            $log->ip_address,
            'The entry does not record where the confirmation came from.',
        );
        $this->assertSame('Mozilla/5.0 test', $log->user_agent, 'The entry does not record the client that made it.');
    }

    public function test_a_manual_settlement_is_distinguishable_from_a_gateway_one(): void
    {
        [$payment] = $this->paymentAndTransaction();

        $this->actingAs($this->paymentClerk());

        $this->post('/finance/payments/'.$payment->id.'/confirm', ['reference_number' => 'TRF-1'])
            ->assertRedirect();

        // The regression this guards: adding provenance must not quietly become
        // "attributed to the gateway" on the path that a person used.
        $log = $this->onlyLog();

        $this->assertSame('payment_confirmed', $log->action);
        $this->assertArrayNotHasKey(
            'gateway',
            $log->new_values ?? [],
            'A payment confirmed by hand is recorded as if a gateway had settled it.',
        );
    }

    // ------------------------------------------------------------------

    private function onlyLog(): AuditLog
    {
        // No `withoutSchoolScope()` here, unlike every other school-owned model:
        // `AuditLog` is one of the three deliberate exclusions from the tenant
        // scope, because it also holds platform and support actions.
        $log = AuditLog::query()->latest('id')->first();

        $this->assertNotNull($log, 'No audit entry was written, so there is nothing to say about its contents.');

        return $log;
    }

    /**
     * A user holding `manage-payments`, which is what the confirm route asks for.
     */
    private function paymentClerk(): User
    {
        return $this->actingAsSchoolUser($this->school, ['manage-payments']);
    }

    private function deliver(Payment $payment, GatewayTransaction $transaction): void
    {
        $response = $this->postJson('/webhooks/payments/moyasar', $this->payload($payment, $transaction));

        // Asserting the outcome rather than the status code: every outcome this
        // endpoint can return is a 2xx, so a 200 on its own would let "ignored"
        // and "replayed" pass as a settlement that never happened.
        $this->assertSame(
            'settled',
            $response->json('status'),
            'status='.$response->status().' body='.$response->getContent().' payment='.$payment->id,
        );
    }

    private function paymentAndTransaction(): array
    {
        $student = Student::factory()->create(['school_id' => $this->school->id, 'email' => null]);

        // Settlement receipts the financial guardian, so a payment with no
        // guardian relationship is not the path under test.
        $guardian = Guardian::create([
            'school_id' => $this->school->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Amina',
            'last_name' => 'Hassan',
            'email' => 'amina@example.test',
        ]);

        GuardianRelationship::create([
            'school_id' => $this->school->id,
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'is_financial_guardian' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
        ]);

        $payment = Payment::create([
            'school_id' => $this->school->id,
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'payment_number' => 'PAY-AUDIT-'.$student->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500,
            'status' => 'pending',
            'payment_method' => 'online',
        ]);

        PaymentAllocation::create([
            'school_id' => $this->school->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        $transaction = GatewayTransaction::create([
            'school_id' => $this->school->id,
            'payment_id' => $payment->id,
            'gateway' => 'moyasar',
            'gateway_transaction_id' => 'trx-audit-'.$payment->id,
            'status' => 'pending',
            'currency' => 'SAR',
            'amount' => 50000,
        ]);

        return [$payment, $transaction];
    }

    /**
     * A Moyasar delivery, in the envelope the provider documents.
     *
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, GatewayTransaction $transaction): array
    {
        return [
            'id' => 'evt-audit-'.$payment->id,
            'type' => 'payment_paid',
            'secret_token' => 'whsec_test_secret',
            'data' => [
                'id' => $transaction->gateway_transaction_id,
                'status' => 'paid',
                'amount' => 50000,
                'currency' => 'SAR',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'invoice_id' => $payment->invoice_id,
                    'school_id' => $payment->school_id,
                ],
            ],
        ];
    }
}
