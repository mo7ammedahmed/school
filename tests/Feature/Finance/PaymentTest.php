<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Finance\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-payments', 'guard_name' => 'web']);
    }

    public function test_payment_page_loads(): void
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

        $response = $this->get('/finance/payments');
        $response->assertStatus(200);
    }

    public function test_payment_can_be_created(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'total_amount' => 1000,
            'balance_due' => 1000,
        ]);
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        $response = $this->post('/finance/payments', [
            'student_id' => $invoice->student_id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-001',
            'payment_date' => '2024-09-01',
            'amount' => 500,
            'currency' => 'SAR',
            'payment_method' => 'cash',
            'status' => 'paid',
            'reference_number' => 'REF-001',
            'notes' => 'Partial payment',
        ]);

        $response->assertRedirect('/finance/payments');
        $this->assertDatabaseHas('payments', [
            'payment_number' => 'PAY-001',
            'school_id' => $school->id,
        ]);
    }
}
