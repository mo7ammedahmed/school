<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvoicePaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_link_renders_the_public_payment_page(): void
    {
        $invoice = $this->invoice();

        $response = $this->get($this->signed('public.invoices.pay', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
        $response->assertSee('Balance due');
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $invoice = $this->invoice();

        $this->get("/pay/{$invoice->id}")->assertForbidden();
    }

    public function test_a_signed_link_for_a_paid_invoice_shows_it_as_settled(): void
    {
        $invoice = $this->invoice();
        $invoice->forceFill(['status' => 'paid', 'amount_paid' => 1150, 'balance_due' => 0])->save();

        $this->get($this->signed('public.invoices.pay', $invoice))
            ->assertOk()
            ->assertSee('Paid in full');
    }

    public function test_online_checkout_is_refused_while_the_gateway_is_not_configured(): void
    {
        $invoice = $this->invoice();

        $response = $this->post($this->signed('public.invoices.checkout', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(0, Payment::count(), 'no payment attempt should be recorded');
    }

    public function test_a_guardian_can_report_a_bank_transfer_from_the_link(): void
    {
        $invoice = $this->invoice();

        $response = $this->post($this->signed('public.invoices.offline', $invoice), [
            'reference' => 'TRX-99887',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'reference_number' => 'TRX-99887',
        ]);
    }

    public function test_reporting_a_transfer_twice_reuses_the_pending_payment(): void
    {
        $invoice = $this->invoice();

        $this->post($this->signed('public.invoices.offline', $invoice), ['reference' => 'A']);
        $this->post($this->signed('public.invoices.offline', $invoice), ['reference' => 'B']);

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_the_payment_page_hides_online_payment_when_the_gateway_is_offline(): void
    {
        $invoice = $this->invoice();

        $this->get($this->signed('public.invoices.pay', $invoice))
            ->assertOk()
            // No checkout form is rendered, and the transfer hint takes its place.
            ->assertDontSee('/checkout')
            ->assertSee('Online card payment is not enabled');
    }

    public function test_the_invoice_pdf_downloads_from_the_signed_link(): void
    {
        $invoice = $this->invoice();

        $response = $this->get($this->signed('public.invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Each form posts to its own route, so the signature has to be built for
     * that route rather than copied from the pay link's query string.
     */
    private function signed(string $route, Invoice $invoice): string
    {
        return URL::temporarySignedRoute($route, now()->addDay(), ['invoice' => $invoice->id]);
    }

    private function invoice(): Invoice
    {
        $school = School::factory()->create();

        return Invoice::factory()->create([
            'school_id' => $school->id,
            'subtotal' => 1000,
            'total_amount' => 1150,
            'balance_due' => 1150,
            'amount_paid' => 0,
            'currency' => 'SAR',
            'status' => 'issued',
        ]);
    }
}
