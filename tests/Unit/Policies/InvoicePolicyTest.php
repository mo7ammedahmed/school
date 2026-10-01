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

        $this->app['session']->put('school_id', $school->id);

        $policy = new InvoicePolicy;
        $this->assertTrue($policy->view($user, $invoice));
    }

    /**
     * The permission is necessary but not sufficient: it is conjoined with the
     * tenant, so a caller whose session points at another school is refused
     * even holding `manage-invoices`.
     *
     * This case passed before the disjunction was corrected, because the
     * permission alone satisfied the `||`. It is here to keep the conjunction
     * from being "simplified" back.
     */
    public function test_the_permission_alone_does_not_open_another_schools_invoice(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-invoices');
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $schoolB->id,
        ]);

        $this->app['session']->put('school_id', $schoolA->id);

        $policy = new InvoicePolicy;
        $this->assertFalse($policy->view($user, $invoice));
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

        $this->app['session']->put('school_id', $school->id);

        $policy = new InvoicePolicy;
        $this->assertFalse($policy->update($user, $invoice));
    }

    /**
     * The controller's update path deliberately keeps the collected amount and
     * recomputes only the balance, and a feature test pins that a partially
     * paid invoice stays partially paid. "Draft only" was the stale half of the
     * rule; `issued`, `paid` and `voided` remain locked.
     */
    public function test_a_partially_paid_invoice_can_still_be_updated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-invoices');
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'status' => 'partially_paid',
        ]);

        $this->app['session']->put('school_id', $school->id);

        $policy = new InvoicePolicy;
        $this->assertTrue($policy->update($user, $invoice));
    }

    public function test_an_issued_invoice_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-invoices');
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'status' => 'issued',
        ]);

        $this->app['session']->put('school_id', $school->id);

        $policy = new InvoicePolicy;
        $this->assertFalse($policy->delete($user, $invoice));
    }
}
