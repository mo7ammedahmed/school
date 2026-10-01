<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Finance\Services\MoyasarGateway;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The gateway is told an amount in minor units, and settlement later asks
 * whether the gateway's own answer matches that same payment. Those two numbers
 * are computed independently, and they were not computed the same way:
 *
 *   charged:  (int) ($payment->amount * 100)       — truncates
 *   expected: (int) round(((float) $amount) * 100) — rounds
 *
 * A binary float cannot hold most two-decimal amounts, so `8.20 * 100` is
 * `819.9999999999999` and the cast sends 819 halalas for an 8.20 payment. The
 * customer is charged 8.19, the gateway reports back 819, and settlement —
 * which expects 820 — calls it a mismatch and refuses. The money is taken and
 * the invoice stays open.
 *
 * 137 of the first 2000 two-decimal amounts truncate to the wrong number of
 * halalas, so this is arithmetic on ordinary inputs rather than a curiosity at
 * the edge of the range. The amounts below are three real ones from that set.
 *
 * The invariant pinned here is the one a customer experiences: what we charge
 * must be what we later accept. Asserting each side against its own literal
 * would only pin today's arithmetic twice — and the existing settlement tests
 * already do that, with a fake that rounds, which is precisely why this was
 * invisible.
 */
class GatewayChargesWhatSettlementExpectsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * What the gateway last charged, in minor units.
     *
     * The fake gateway confirms this figure, so a settlement is only ever asked
     * about the amount that was actually sent.
     */
    private int $charged = 0;

    /**
     * Two-decimal amounts whose float form truncates a halala below the truth.
     *
     * @return array<string, array{string}>
     */
    public static function lossyAmounts(): array
    {
        return [
            'eight twenty' => ['8.20'],
            'thirty three thirty' => ['33.30'],
            'twenty nine halalas' => ['0.29'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // One fake for the whole test. A bare `Http::fake()` registered first
        // would answer every later request with an empty body, and an empty
        // body reads to settlement as "the gateway could not be reached".
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/payments')) {
                return Http::response(['id' => 'trx_created', 'status' => 'paid']);
            }

            return Http::response([
                'id' => basename((string) parse_url($request->url(), PHP_URL_PATH)),
                'status' => 'paid',
                'amount' => $this->charged,
                'currency' => 'SAR',
            ]);
        });
    }

    #[DataProvider('lossyAmounts')]
    public function test_the_amount_charged_is_the_amount_settlement_expects(string $amount): void
    {
        [$payment] = $this->paymentFor($amount);

        $charged = $this->chargeThroughGateway($payment);

        $this->assertSame(
            (int) round(((float) $payment->fresh()->amount) * 100),
            $charged,
            "A payment of {$amount} was charged {$charged} minor units while settlement expects "
            .'more. The customer pays the wrong amount and the payment can never settle.',
        );
    }

    #[DataProvider('lossyAmounts')]
    public function test_a_payment_the_gateway_confirms_is_not_refused_as_a_mismatch(string $amount): void
    {
        [$payment, $transaction, $invoice] = $this->paymentFor($amount);

        // Charge it for real, then have the gateway confirm exactly what it was
        // charged. That is the sequence a customer lives through: whatever we
        // asked for is what they are later quoted, and it has to be what we
        // then accept.
        $this->chargeThroughGateway($payment);

        $response = $this->postJson(
            '/webhooks/payments/moyasar',
            $this->payload($payment, $transaction, $this->charged)
        );

        $this->assertSame(
            200,
            $response->status(),
            "status={$response->status()} body={$response->getContent()} charged={$this->charged}",
        );
        $response->assertJson(['status' => 'settled']);

        $this->assertSame(
            'paid',
            $payment->fresh()->status,
            "A payment of {$amount} was charged {$this->charged} minor units, the gateway confirmed that "
            .'same figure, and settlement refused it anyway.',
        );
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    // ------------------------------------------------------------------

    /**
     * Charge the payment through the real gateway and report the minor units
     * that were sent.
     */
    private function chargeThroughGateway(Payment $payment): int
    {
        $gateway = (new MoyasarGateway)->useSchoolSettings($payment->school_id);

        $this->assertTrue(
            $gateway->isConfigured(),
            'The gateway has no usable credentials for this school, so no charge was attempted and the '
            .'amount under test was never sent.',
        );

        $result = $gateway->createPayment($payment);

        $this->assertTrue(
            $result['success'] ?? false,
            'The gateway declined to charge: '.json_encode($result),
        );

        Http::assertSent(function (ClientRequest $request) {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/payments')) {
                return false;
            }

            $this->assertIsInt(
                $request->data()['amount'] ?? null,
                'The gateway was asked to charge a non-integer number of minor units.',
            );

            return true;
        });

        /** @var ClientRequest $charge */
        $charge = Http::recorded()[0][0];

        $this->charged = (int) $charge->data()['amount'];

        return $this->charged;
    }

    /**
     * @return array{0: Payment, 1: GatewayTransaction, 2: Invoice}
     */
    private function paymentFor(string $amount): array
    {
        $school = School::factory()->create();

        // The tenant scope hides school-owned rows from any query made before a
        // request pins a school, and the gateway describes the charge by reading
        // `$payment->invoice->invoice_number`. Without this the invoice relation
        // resolves to nothing and no charge is attempted at all.
        $this->app->make(TenantContext::class)->set($school->id);

        GatewaySettings::for($school)->save([
            'gateway' => 'moyasar',
            'enabled' => true,
            'public_key' => 'pk_test',
            'secret_key' => 'sk_test',
            'webhook_secret' => 'whsec_test_secret',
        ]);

        $student = Student::factory()->create(['school_id' => $school->id, 'email' => null]);

        // Settlement receipts the financial guardian, so a payment with no
        // guardian is not the path these tests are about.
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
            'subtotal' => $amount,
            'total_amount' => $amount,
            'balance_due' => $amount,
            'amount_paid' => 0,
            'currency' => 'SAR',
        ]);

        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-'.uniqid(),
            'payment_date' => now()->toDateString(),
            'amount' => $amount,
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
            'amount' => $amount,
        ]);

        return [$payment, $transaction, $invoice];
    }

    /**
     * A Moyasar delivery, in the envelope the provider documents.
     *
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, GatewayTransaction $transaction, int $minorAmount): array
    {
        return [
            'id' => 'evt_'.$payment->id,
            'type' => 'payment_paid',
            'secret_token' => 'whsec_test_secret',
            'data' => [
                'id' => $transaction->gateway_transaction_id,
                'status' => 'paid',
                'amount' => $minorAmount,
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
