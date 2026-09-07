<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\Finance\Models\Invoice;
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

    public function test_invoice_page_loads(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

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

        $response->assertRedirect('/finance/invoices');
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'school_id' => $school->id,
        ]);
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
