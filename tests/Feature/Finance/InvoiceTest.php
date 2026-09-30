<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-invoices', 'guard_name' => 'web']);
    }

    /**
     * @return array{0: User, 1: School, 2: Student}
     */
    private function schoolUser(): array
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo('manage-invoices');

        return [$user, $school, $student];
    }

    public function test_invoice_page_loads(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo('manage-invoices');

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $response = $this->get('/finance/invoices');
        $response->assertStatus(200);
    }

    public function test_invoice_can_be_created(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $school->id,
        ]);
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo('manage-invoices');

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $response = $this->post('/finance/invoices', [
            'student_id' => $student->id,
            'invoice_number' => 'INV-001',
            'issue_date' => '2024-09-01',
            'due_date' => '2024-09-30',
            'subtotal' => 1000,
            'tax_amount' => 150,
            'tax_rate' => 15,
            'discount_amount' => 0,
            'total_amount' => 1150,
            'amount_paid' => 0,
            'balance_due' => 1150,
            'currency' => 'SAR',
            'status' => 'draft',
            'notes' => 'Test invoice',
        ]);

        // Creation lands on the invoice so it can be issued (and delivered) next.
        $response->assertRedirect('/finance/invoices/1');
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'school_id' => $school->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Issuing is a separate action that delivers the invoice to the guardian, so
     * a status submitted with the create form must not skip it. The form used to
     * offer a status dropdown that the controller discarded.
     */
    public function test_a_new_invoice_is_always_a_draft_whatever_status_is_submitted(): void
    {
        [$user, $school, $student] = $this->schoolUser();

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $this->post('/finance/invoices', [
            'student_id' => $student->id,
            'invoice_number' => 'INV-STATUS',
            'due_date' => '2024-09-30',
            'subtotal' => 1000,
            'status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-STATUS',
            'status' => 'draft',
        ]);
    }

    public function test_editing_an_invoice_cannot_change_its_status(): void
    {
        [$user, $school, $student] = $this->schoolUser();

        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $this->put("/finance/invoices/{$invoice->id}", [
            'student_id' => $student->id,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => '2024-09-30',
            'subtotal' => 1000,
            'status' => 'paid',
        ])->assertRedirect();

        $this->assertSame('draft', $invoice->refresh()->status);
    }

    /**
     * `partially_paid` was not in the edit form's status list, so the browser
     * fell back to the first option and saving rewrote the invoice as a draft.
     */
    public function test_editing_a_partially_paid_invoice_keeps_it_partially_paid(): void
    {
        [$user, $school, $student] = $this->schoolUser();

        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'partially_paid',
            'amount_paid' => 100,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $this->put("/finance/invoices/{$invoice->id}", [
            'student_id' => $student->id,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => '2024-09-30',
            'subtotal' => 1000,
            // What the edit form actually posted: its status dropdown had no
            // `partially_paid` option, so the browser fell back to the first one.
            'status' => 'draft',
        ])->assertRedirect();

        $invoice->refresh();

        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame(100.0, (float) $invoice->amount_paid);
    }

    public function test_issued_invoice_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $school->id,
        ]);
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo('manage-invoices');

        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'issued',
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $response = $this->put("/finance/invoices/{$invoice->id}", [
            'student_id' => $student->id,
            'invoice_number' => 'INV-001-UPDATED',
            'issue_date' => '2024-09-01',
            'due_date' => '2024-09-30',
            'subtotal' => 1000,
            'tax_amount' => 150,
            'tax_rate' => 15,
            'discount_amount' => 0,
            'total_amount' => 1150,
            'amount_paid' => 0,
            'balance_due' => 1150,
            'currency' => 'SAR',
            'status' => 'issued',
            'notes' => 'Updated',
        ]);

        $response->assertStatus(403);
    }
}
