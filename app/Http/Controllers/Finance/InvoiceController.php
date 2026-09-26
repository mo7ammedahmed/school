<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Actions\IssueInvoice;
use App\Domain\Finance\Events\InvoiceCreated;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Finance\Services\InvoiceDeliveryService;
use App\Domain\Finance\Services\InvoiceLinks;
use App\Domain\Finance\Services\InvoicePdf;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceDeliveryService $delivery) {}

    public function index(Request $request): InertiaResponse
    {
        $query = Invoice::query()
            ->where('school_id', $this->schoolId())
            ->with('student:id,first_name,last_name,student_number');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(function ($inner) use ($search): void {
                $inner->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($student) use ($search): void {
                        $student->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->latest()->get()->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'student' => $invoice->student ? [
                'first_name' => $invoice->student->first_name,
                'last_name' => $invoice->student->last_name,
            ] : null,
            'issue_date' => optional($invoice->issue_date)->toDateString(),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'total_amount' => (float) $invoice->total_amount,
            'balance_due' => (float) $invoice->balance_due,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
            'sent_at' => optional($invoice->sent_at)->toIso8601String(),
            'delivery_channels' => $invoice->delivery_channels ?? [],
        ]);

        return Inertia::render('finance/invoices/index', [
            'invoices' => $invoices,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('finance/invoices/create', [
            'students' => $this->students(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', $this->studentExistsRule()],
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'issue_date' => 'nullable|date',
            'due_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $amounts = $this->amounts($validated);

        $invoice = Invoice::create([
            'school_id' => $this->schoolId(),
            'student_id' => $validated['student_id'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'] ?? now()->toDateString(),
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'currency' => $this->currency(),
            'status' => 'draft',
        ] + $amounts);

        InvoiceCreated::dispatch($invoice->id, (int) $invoice->student_id, (float) $invoice->total_amount);

        return redirect()
            ->route('finance.invoices.show', $invoice)
            ->with('success', 'Invoice created. Issue it to send the payment link to the guardian.');
    }

    public function show(Invoice $invoice): InertiaResponse
    {
        $this->authorizeInvoice($invoice);

        $invoice->load(['student', 'lines', 'payments']);

        return Inertia::render('finance/invoices/show', [
            'invoice' => array_merge($invoice->toArray(), [
                'outstanding' => $invoice->outstanding(),
            ]),
            'payUrl' => $invoice->isPaid() ? null : InvoiceLinks::payUrl($invoice),
            'guardians' => $invoice->guardians()->map(fn ($guardian) => [
                'id' => $guardian->id,
                'name' => trim($guardian->first_name.' '.$guardian->last_name),
                'email' => $guardian->email,
                'phone' => $guardian->phone,
                'is_financial' => (bool) $guardian->is_financial_guardian,
            ])->values(),
            'delivery' => [
                'sent_at' => optional($invoice->sent_at)->toIso8601String(),
                'reminder_sent_at' => optional($invoice->reminder_sent_at)->toIso8601String(),
                'channels' => $invoice->delivery_channels ?? [],
                'auto_send' => GatewaySettings::for((int) $invoice->school_id)->autoSend(),
            ],
        ]);
    }

    public function edit(Invoice $invoice): InertiaResponse
    {
        $this->authorizeInvoice($invoice);

        $this->abortIfLocked($invoice, 'Issued invoices cannot be edited. Void it and raise a new one instead.');

        return Inertia::render('finance/invoices/edit', [
            'invoice' => $invoice->toArray(),
            'students' => $this->students(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        $this->abortIfLocked($invoice, 'Issued invoices cannot be edited. Void it and raise a new one instead.');

        $validated = $request->validate([
            'student_id' => ['required', 'integer', $this->studentExistsRule()],
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number,'.$invoice->id,
            'issue_date' => 'nullable|date',
            'due_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,issued,partially_paid,paid,overdue,void',
            'notes' => 'nullable|string',
        ]);

        $amounts = $this->amounts($validated);

        // Keep whatever has already been collected; only the total changed.
        $alreadyPaid = (float) $invoice->amount_paid;
        $amounts['amount_paid'] = $alreadyPaid;
        $amounts['balance_due'] = max(0.0, round($amounts['total_amount'] - $alreadyPaid, 4));

        $invoice->update([
            'student_id' => $validated['student_id'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'] ?? now()->toDateString(),
            'due_date' => $validated['due_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ] + $amounts);

        return redirect()
            ->route('finance.invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        abort_if((float) $invoice->amount_paid > 0, 403, 'Invoices with payments cannot be deleted.');

        $invoice->delete();

        return redirect()->route('finance.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Publish the invoice. Delivery is automatic unless the school turns it off.
     */
    public function issue(Invoice $invoice, IssueInvoice $issueInvoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        abort_if($invoice->isPaid(), 403, 'This invoice is already settled.');

        $issueInvoice->execute($invoice);
        $invoice->refresh();

        $summary = $this->deliverySummary($invoice);

        return back()->with('success', 'Invoice issued. '.$summary);
    }

    /**
     * Manual resend — a reminder once it has already gone out.
     */
    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        $kind = $invoice->sent_at === null
            ? InvoiceMail::KIND_ISSUED
            : InvoiceMail::KIND_REMINDER;

        $result = $this->delivery->deliver($invoice, $kind);

        $message = $result['detail'];

        if ($result['errors'] !== []) {
            $message .= ' '.implode(' ', array_slice($result['errors'], 0, 3));
        }

        return back()->with(
            array_filter($result['channels']) === [] ? 'error' : 'success',
            $message,
        );
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorizeInvoice($invoice);

        return InvoicePdf::download($invoice);
    }

    private function deliverySummary(Invoice $invoice): string
    {
        $channels = array_keys(array_filter($invoice->delivery_channels ?? []));

        if ($channels === []) {
            return 'Nothing was delivered — add a guardian email or phone number, then resend.';
        }

        return 'Sent to the guardian via '.implode(', ', $channels).'.';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function amounts(array $validated): array
    {
        $subtotal = (float) ($validated['subtotal'] ?? 0);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 4);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $total = round($subtotal + $taxAmount - $discount, 4);
        $paid = 0.0;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            // Stored as a fraction so it fits the decimal(5,4) column.
            'tax_rate' => round($taxRate / 100, 4),
            'discount_amount' => $discount,
            'total_amount' => $total,
            'amount_paid' => $paid,
            'balance_due' => $total,
        ];
    }

    private function students(): Collection
    {
        return Student::query()
            ->where('school_id', $this->schoolId())
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'student_number']);
    }

    private function studentExistsRule(): Exists
    {
        return Rule::exists('students', 'id')->where('school_id', $this->schoolId());
    }

    private function currency(): string
    {
        return GatewaySettings::for($this->schoolId())->currency();
    }

    private function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        return $schoolId;
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless((int) $invoice->school_id === $this->schoolId(), 403);
    }

    /**
     * Once an invoice has been handed to a guardian it is a financial document:
     * edits go through void-and-reissue rather than silent mutation.
     */
    private function abortIfLocked(Invoice $invoice, string $message): void
    {
        abort_if(in_array($invoice->status, ['issued', 'paid', 'void'], true), 403, $message);
    }
}
