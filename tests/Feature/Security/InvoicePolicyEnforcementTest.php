<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Finance\Models\Invoice;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * `InvoicePolicy` is the written answer to what may be done to an invoice, but
 * nothing consulted it: the controller carried its own, weaker copy of the
 * rule, and the two disagreed about all three write abilities.
 *
 * Sorting them out meant deciding which layer was right, per ability, from the
 * evidence rather than from the policy alone:
 *
 *  - `update`: the controller allows a `partially_paid` invoice and
 *    deliberately keeps the collected amount, and
 *    `InvoiceTest::test_editing_a_partially_paid_invoice_keeps_it_partially_paid`
 *    pins that. The policy's "draft only" was the stale half, so the policy was
 *    corrected to `draft|partially_paid` and the controller now asks for it.
 *  - `delete` and `issue`: no screen offers either on a non-draft invoice, no
 *    test relied on it, and `send` is the resend path — so the policy's
 *    draft-only rule is enforced. An issued invoice can no longer be deleted
 *    (its payments-guard only caught paid ones) or issued a second time, which
 *    re-announced and re-delivered it.
 *
 * The controller's local `abortIfLocked()` went with them: it checked a status
 * the enum spells `voided` as `void`, and the policy now covers every status.
 * The caller holds `manage-invoices`, so the group middleware is a non-factor
 * and every refusal below can only have come from the action.
 */
class InvoicePolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->school = School::factory()->create();

        Permission::findOrCreate('manage-invoices', 'web');
    }

    public function test_an_issued_invoice_cannot_be_opened_for_editing(): void
    {
        $invoice = $this->invoice('issued');

        $this->signIn();

        $this->get("/finance/invoices/{$invoice->id}/edit")->assertForbidden();
    }

    public function test_an_issued_invoice_cannot_be_updated(): void
    {
        $invoice = $this->invoice('issued');

        $this->signIn();

        $this->put("/finance/invoices/{$invoice->id}", $this->payload($invoice, 'ATTEMPTED-UPDATE'))
            ->assertForbidden();

        $this->assertSame(
            $invoice->invoice_number,
            $invoice->fresh()?->invoice_number,
            'The request was refused but the invoice was written anyway; the refusal has to happen '
            .'before the mutation, not after it.',
        );
    }

    public function test_an_issued_invoice_cannot_be_deleted(): void
    {
        $invoice = $this->invoice('issued');

        $this->signIn();

        $this->delete("/finance/invoices/{$invoice->id}")->assertForbidden();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'deleted_at' => null,
        ]);
    }

    public function test_an_issued_invoice_cannot_be_issued_again(): void
    {
        $invoice = $this->invoice('issued');

        $this->signIn();

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertForbidden();
    }

    public function test_a_partially_paid_invoice_cannot_be_issued_again(): void
    {
        $invoice = $this->invoice('partially_paid', amountPaid: 40);

        $this->signIn();

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertForbidden();
    }

    // ------------------------------------------------------------------
    // The other half: the policy must not refuse work it says is allowed.
    // A guard that denies everything would satisfy the cases above.
    // ------------------------------------------------------------------

    public function test_a_partially_paid_invoice_can_still_be_updated(): void
    {
        $invoice = $this->invoice('partially_paid', amountPaid: 40);

        $this->signIn();

        $this->put("/finance/invoices/{$invoice->id}", $this->payload($invoice, $invoice->invoice_number))
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame(40.0, (float) $invoice->amount_paid, 'The collected amount was not preserved.');
    }

    public function test_a_draft_invoice_can_still_be_edited(): void
    {
        $invoice = $this->invoice('draft');

        $this->signIn();

        $this->get("/finance/invoices/{$invoice->id}/edit")->assertSuccessful();
    }

    public function test_a_draft_invoice_can_still_be_deleted(): void
    {
        $invoice = $this->invoice('draft');

        $this->signIn();

        $this->delete("/finance/invoices/{$invoice->id}")->assertRedirect();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_a_draft_invoice_can_still_be_issued(): void
    {
        $invoice = $this->invoice('draft');

        $this->signIn();

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        $this->assertSame('issued', $invoice->fresh()?->status);
    }

    // ------------------------------------------------------------------

    private function signIn(): User
    {
        return $this->actingAsSchoolUser($this->school, ['manage-invoices']);
    }

    private function invoice(string $status, float $amountPaid = 0): Invoice
    {
        $student = Student::factory()->create(['school_id' => $this->school->id]);

        return Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'status' => $status,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_amount' => 15,
            'tax_rate' => 0.15,
            'total_amount' => 115,
            'amount_paid' => $amountPaid,
            'balance_due' => 115 - $amountPaid,
        ]);
    }

    /**
     * A payload the update path would accept if it ever got that far — the
     * point of the red case is that it does not reach validation now.
     *
     * @return array<string, mixed>
     */
    private function payload(Invoice $invoice, string $number): array
    {
        return [
            'student_id' => $invoice->student_id,
            'invoice_number' => $number,
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 100,
            'tax_rate' => 15,
            'discount_amount' => 0,
            'notes' => 'edited',
        ];
    }
}
