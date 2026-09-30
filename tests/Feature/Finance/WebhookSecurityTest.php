<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Mail\InvoiceMail;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The webhook endpoint is the only place the application accepts money
 * instructions from the internet, and until this suite it accepted them from
 * anyone: no signature, no secret, no question asked of the gateway.
 *
 * A real Moyasar delivery carries the documented envelope — `id`, `type`,
 * `secret_token`, and a `data` object holding the payment. The previous tests
 * posted a flat shape the provider never sends, which is why the missing
 * verification was invisible: the endpoint never reached the code that would
 * have used a signed field.
 *
 * Every case here posts that envelope. The ones that must settle say so by
 * agreement with the gateway's own answer, not by the payload's claim.
 */
class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->fakeGatewayAskingPaid();
    }

    /**
     * A gateway that confirms what the local records say, so a test only has to
     * disagree with them when it means to.
     */
    private function fakeGatewayAskingPaid(): void
    {
        Http::fake(function (ClientRequest $request) {
            $id = basename((string) parse_url($request->url(), PHP_URL_PATH));
            $transaction = GatewayTransaction::query()->where('gateway_transaction_id', $id)->first();

            return Http::response([
                'id' => $id,
                'status' => 'paid',
                'amount' => (int) round(((float) ($transaction?->amount ?? 0)) * 100),
                'currency' => strtoupper((string) ($transaction?->currency ?? 'SAR')),
            ]);
        });
    }

    public function test_an_unsigned_webhook_is_rejected_and_settles_nothing(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'secret_token' => null,
        ]))->assertStatus(401);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
        $this->assertSame(0, WebhookEvent::where('status', 'completed')->count());
        $this->assertSame(0, GatewayTransaction::where('status', 'completed')->count());
        Mail::assertNothingSent();
    }

    public function test_a_webhook_signed_with_the_wrong_secret_is_rejected(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'secret_token' => 'not-the-secret',
        ]))->assertStatus(401);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
        Mail::assertNothingSent();
    }

    public function test_a_webhook_is_rejected_when_the_school_has_no_webhook_secret(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment(withWebhookSecret: false);

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction))
            ->assertStatus(401);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
    }

    public function test_a_verified_webhook_settles_the_invoice_and_receipts_the_guardian(): void
    {
        [$invoice, $payment, $transaction, $guardian] = $this->invoiceWithPendingPayment();

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction))
            ->assertOk()
            ->assertJson(['status' => 'settled']);

        $payment->refresh();
        $invoice->refresh();
        $transaction->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->balance_due, 0.001);
        $this->assertEqualsWithDelta(1150.0, (float) $invoice->amount_paid, 0.001);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame('completed', $transaction->status);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);
        $this->assertDatabaseHas('webhook_events', [
            'gateway' => 'moyasar',
            'event_id' => 'evt_'.$transaction->gateway_transaction_id,
            'status' => 'completed',
        ]);

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->kind === InvoiceMail::KIND_RECEIPT
            && $mail->invoice->id === $invoice->id
            && $mail->hasTo($guardian->email));
    }

    public function test_a_replayed_webhook_settles_only_once(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        $payload = $this->envelope($payment, $transaction);

        $this->postJson('/webhooks/payments/moyasar', $payload)->assertOk();
        $this->postJson('/webhooks/payments/moyasar', $payload)
            ->assertOk()
            ->assertJson(['status' => 'replayed']);

        $invoice->refresh();

        $this->assertSame(1, WebhookEvent::count());
        $this->assertEqualsWithDelta(1150.0, (float) $invoice->amount_paid, 0.001, 'the invoice must not be paid twice');
        Mail::assertSentCount(1);
    }

    public function test_an_amount_mismatch_is_rejected(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        Http::fake(['api.moyasar.com/*' => Http::response([
            'id' => $transaction->gateway_transaction_id,
            'status' => 'paid',
            'amount' => 99900,
            'currency' => 'SAR',
        ])]);

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction))
            ->assertStatus(422);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
        $this->assertSame(1, WebhookEvent::where('status', 'failed')->count());
        Mail::assertNothingSent();
    }

    public function test_a_currency_mismatch_is_rejected(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        Http::fake(['api.moyasar.com/*' => Http::response([
            'id' => $transaction->gateway_transaction_id,
            'status' => 'paid',
            'amount' => 115000,
            'currency' => 'USD',
        ])]);

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction))
            ->assertStatus(422);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
    }

    public function test_the_payloads_claim_of_success_is_not_enough_to_settle(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        Http::fake(['api.moyasar.com/*' => Http::response([
            'id' => $transaction->gateway_transaction_id,
            'status' => 'failed',
            'amount' => 115000,
            'currency' => 'SAR',
        ])]);

        // The envelope insists the payment succeeded. Only the gateway's own
        // answer is allowed to decide, so nothing may settle.
        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'data' => ['status' => 'paid'],
        ]))->assertOk();

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('issued', $invoice->refresh()->status);
        $this->assertSame('failed', $transaction->refresh()->status);
        Mail::assertNothingSent();
    }

    public function test_the_gateway_is_asked_to_confirm_the_payment(): void
    {
        [, $payment, $transaction] = $this->invoiceWithPendingPayment();

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction))->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'GET'
            && str_contains($request->url(), '/payments/'.$transaction->gateway_transaction_id));
    }

    public function test_a_failed_delivery_can_be_retried_while_a_completed_one_cannot(): void
    {
        [$invoice, $payment, $transaction] = $this->invoiceWithPendingPayment();

        WebhookEvent::create([
            'school_id' => $payment->school_id,
            'gateway' => 'moyasar',
            'event_id' => 'evt_retry',
            'event_type' => 'payment_paid',
            'payload' => [],
            'status' => 'failed',
            'error_message' => 'The gateway could not be reached.',
        ]);

        // The first delivery failed; the provider tries again and it must work.
        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'id' => 'evt_retry',
        ]))->assertOk()->assertJson(['status' => 'settled']);

        $this->assertSame('paid', $payment->refresh()->status);
        $this->assertSame(1, WebhookEvent::where('gateway', 'moyasar')->where('event_id', 'evt_retry')->count());
        $this->assertSame('completed', WebhookEvent::where('event_id', 'evt_retry')->first()->status);

        // Once completed, the same delivery is a replay, not a second payment.
        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'id' => 'evt_retry',
        ]))->assertOk()->assertJson(['status' => 'replayed']);

        $this->assertSame('completed', WebhookEvent::where('event_id', 'evt_retry')->first()->status);
        $this->assertEqualsWithDelta(1150.0, (float) $invoice->refresh()->amount_paid, 0.001);
        Mail::assertSentCount(1);
    }

    public function test_a_webhook_for_an_unknown_payment_writes_nothing(): void
    {
        $school = School::factory()->create();

        GatewaySettings::for($school)->save(['webhook_secret' => self::SECRET]);

        $this->postJson('/webhooks/payments/moyasar', [
            'id' => 'evt_unknown',
            'type' => 'payment_paid',
            'secret_token' => self::SECRET,
            'data' => [
                'id' => 'trx_unknown',
                'status' => 'paid',
                'amount' => 1000,
                'currency' => 'SAR',
                'metadata' => ['payment_id' => 999999],
            ],
        ])->assertOk();

        $this->assertSame(0, WebhookEvent::count());
        $this->assertSame(0, GatewayTransaction::where('status', 'completed')->count());
    }

    public function test_an_unimplemented_gateway_is_refused(): void
    {
        $this->postJson('/webhooks/payments/stripe', [
            'id' => 'evt_stripe',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['metadata' => ['payment_id' => 1]]],
        ])->assertNotFound();

        $this->assertSame(0, WebhookEvent::count());
    }

    public function test_a_webhook_request_does_not_need_a_csrf_token(): void
    {
        $this->assertContains(
            'webhooks/*',
            $this->app->make(ValidateCsrfToken::class)->getExcludedPaths(),
            'The webhook path is not exempt from CSRF, so a real delivery would be answered with 419.',
        );

        [, $payment, $transaction] = $this->invoiceWithPendingPayment();

        // The suite normally skips CSRF for every request. Leaving the testing
        // environment makes the check run for real, so the exemption is what
        // lets this request reach the controller instead of a 419.
        $this->app['env'] = 'local';

        $this->postJson('/webhooks/payments/moyasar', $this->envelope($payment, $transaction, [
            'secret_token' => 'not-the-secret',
        ]))->assertStatus(401);
    }

    public function test_the_webhook_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/webhooks/payments/moyasar', ['id' => 'evt_ping_'.$i])->assertOk();
        }

        $this->postJson('/webhooks/payments/moyasar', ['id' => 'evt_ping_61'])->assertStatus(429);
    }

    public function test_a_webhook_failure_does_not_log_the_payload(): void
    {
        Log::spy();

        $this->postJson('/webhooks/payments/moyasar', [
            'data' => ['card' => ['last4' => '4242']],
        ])->assertOk();

        Log::shouldNotHaveReceived('warning', function (string $message, array $context = []): bool {
            return str_contains((string) json_encode($context), '4242');
        });

        Log::shouldNotHaveReceived('error', function (string $message, array $context = []): bool {
            return str_contains((string) json_encode($context), '4242');
        });
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    /**
     * @return array{0: Invoice, 1: Payment, 2: GatewayTransaction, 3: Guardian}
     */
    private function invoiceWithPendingPayment(bool $withWebhookSecret = true): array
    {
        $school = School::factory()->create();

        GatewaySettings::for($school)->save([
            'gateway' => 'moyasar',
            'enabled' => true,
            'public_key' => 'pk_test',
            'secret_key' => 'sk_test',
            'webhook_secret' => $withWebhookSecret ? self::SECRET : null,
        ]);

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
            'payment_number' => 'PAY-WH-'.uniqid(),
            'payment_date' => now()->toDateString(),
            'amount' => 1150,
            'currency' => 'SAR',
            'payment_method' => 'moyasar',
            'status' => 'pending',
        ]);

        $transaction = GatewayTransaction::create([
            'school_id' => $school->id,
            'payment_id' => $payment->id,
            'gateway' => 'moyasar',
            'gateway_transaction_id' => 'trx-'.$payment->id,
            'status' => 'pending',
            'currency' => 'SAR',
            'amount' => 1150,
        ]);

        return [$invoice, $payment, $transaction, $guardian];
    }

    /**
     * A Moyasar delivery, in the envelope the provider documents.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function envelope(Payment $payment, GatewayTransaction $transaction, array $overrides = []): array
    {
        $payload = [
            'id' => 'evt_'.$transaction->gateway_transaction_id,
            'type' => 'payment_paid',
            'created_at' => now()->toIso8601String(),
            'secret_token' => self::SECRET,
            'account_name' => 'Tests',
            'live' => false,
            'data' => [
                'id' => $transaction->gateway_transaction_id,
                'status' => 'paid',
                'amount' => 115000,
                'currency' => 'SAR',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'invoice_id' => $payment->invoice_id,
                    'student_id' => $payment->student_id,
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
