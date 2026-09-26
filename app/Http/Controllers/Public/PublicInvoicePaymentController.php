<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Finance\Services\InvoiceLinks;
use App\Domain\Finance\Services\InvoicePdf;
use App\Domain\Finance\Services\MoyasarGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page a guardian reaches from the payment link in their invoice email.
 *
 * Access is proven by a signed URL rather than a login, so a guardian never has
 * to create an account to pay. Every route here is outside the auth group.
 */
class PublicInvoicePaymentController extends Controller
{
    public function __construct(private readonly MoyasarGateway $gateway) {}

    public function show(Invoice $invoice): View
    {
        $invoice->loadMissing(['school', 'student', 'lines', 'payments']);

        $settings = GatewaySettings::for((int) $invoice->school_id);

        return view('invoices.pay', [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'student' => $invoice->student,
            'lines' => $invoice->lines,
            'settings' => $settings,
            'outstanding' => $invoice->outstanding(),
            'paid' => $invoice->isPaid(),
            'onlineAvailable' => $settings->supportsOnlineCheckout() && ! $invoice->isPaid(),
            'justPaid' => (bool) session('invoice_paid', false),
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        return InvoicePdf::download($invoice);
    }

    /**
     * Start an online checkout with the school's configured provider.
     */
    public function checkout(Request $request, Invoice $invoice): RedirectResponse
    {
        $settings = GatewaySettings::for((int) $invoice->school_id);

        if ($invoice->isPaid()) {
            return redirect()->to(InvoiceLinks::payUrl($invoice));
        }

        if (! $settings->supportsOnlineCheckout()) {
            return back()->with('error', 'Online payment is not available. Please follow the transfer instructions.');
        }

        $payment = $this->pendingPayment($invoice, $settings->gateway());
        $this->gateway->useSchoolSettings((int) $invoice->school_id);

        $result = $this->gateway->createPayment($payment, [
            'payment_id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'invoice_id' => $invoice->id,
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'return_url' => InvoiceLinks::payUrl($invoice),
        ]);

        $transactionId = $result['transaction_id'] ?? null;

        if ($transactionId) {
            GatewayTransaction::create([
                'school_id' => $invoice->school_id,
                'payment_id' => $payment->id,
                'gateway' => $settings->gateway(),
                'gateway_transaction_id' => (string) $transactionId,
                'status' => 'pending',
                'amount' => (float) $payment->amount,
                'response' => $result['data'] ?? null,
            ]);
        }

        $redirect = $this->checkoutUrl($result);

        if ($redirect === null) {
            return back()->with('error', 'The payment provider could not start a checkout session. Please try again or contact the school.');
        }

        return redirect()->away($redirect);
    }

    /**
     * The offline path: a guardian says they have transferred the money, which
     * records a pending payment for the finance team to confirm.
     */
    public function offline(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($invoice->isPaid()) {
            return redirect()->to(InvoiceLinks::payUrl($invoice));
        }

        $payment = $this->pendingPayment($invoice, 'bank_transfer');
        $payment->update(['reference_number' => $request->string('reference')->toString() ?: $payment->reference_number]);

        return redirect()
            ->to(InvoiceLinks::payUrl($invoice))
            ->with('success', 'Thank you. The school will confirm your transfer shortly.');
    }

    /**
     * A pending payment for the full outstanding balance, reused if the guardian
     * retries so we do not leave a trail of abandoned payment rows.
     */
    private function pendingPayment(Invoice $invoice, string $method): Payment
    {
        $existing = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->where('payment_method', $method)
            ->latest()
            ->first();

        if ($existing !== null && abs((float) $existing->amount - $invoice->outstanding()) < 0.01) {
            return $existing;
        }

        return Payment::create([
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-'.$invoice->invoice_number.'-'.Str::upper(Str::random(5)),
            'payment_date' => now()->toDateString(),
            'amount' => $invoice->outstanding(),
            'currency' => $invoice->currency ?: 'SAR',
            'payment_method' => $method,
            'status' => 'pending',
            'notes' => 'Created from the public payment link.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function checkoutUrl(array $result): ?string
    {
        if (! ($result['success'] ?? false)) {
            return null;
        }

        $data = $result['data'] ?? [];

        if (! is_array($data)) {
            return null;
        }

        foreach ([$data['source']['transaction_url'] ?? null, $data['transaction_url'] ?? null, $data['url'] ?? null] as $candidate) {
            if (is_string($candidate) && str_starts_with($candidate, 'http')) {
                return $candidate;
            }
        }

        $id = $data['id'] ?? null;

        // Fall back to Moyasar's hosted checkout page for the created payment.
        return is_string($id) && $id !== '' ? "https://checkout.moyasar.com/payment/{$id}" : null;
    }
}
