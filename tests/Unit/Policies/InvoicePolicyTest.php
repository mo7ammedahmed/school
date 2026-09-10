<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Policies\InvoicePolicy;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-invoices', 'guard_name' => 'web']);
    }

    public function test_user_with_permission_can_view_invoice(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-invoices');
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
        ]);

        $policy = new InvoicePolicy;
        $this->assertTrue($policy->view($user, $invoice));
    }

    public function test_user_without_permission_cannot_view_other_school_invoice(): void
    {
        $user = User::factory()->create();
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $schoolB->id,
        ]);

        $this->app['session']->put('school_id', $schoolA->id);

        $policy = new InvoicePolicy;
        $this->assertFalse($policy->view($user, $invoice));
    }

    public function test_user_cannot_update_paid_invoice(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-invoices');
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'status' => 'paid',
        ]);

        $policy = new InvoicePolicy;
        $this->assertFalse($policy->update($user, $invoice));
    }
}
