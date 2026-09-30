<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\Finance\Models\FeeStructure;
use App\Domain\Finance\Models\FeeType;
use App\Domain\Finance\Policies\FeeStructurePolicy;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FeeStructurePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-fee-structures', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage-students', 'guard_name' => 'web']);
    }

    public function test_user_with_permission_can_update_same_school_fee_structure(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-fee-structures');
        $feeStructure = $this->makeFeeStructure();

        $this->app['session']->put('school_id', $feeStructure->school_id);

        $policy = new FeeStructurePolicy;
        $this->assertTrue($policy->update($user, $feeStructure));
    }

    public function test_user_with_permission_can_delete_same_school_fee_structure(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-fee-structures');
        $feeStructure = $this->makeFeeStructure();

        $this->app['session']->put('school_id', $feeStructure->school_id);

        $policy = new FeeStructurePolicy;
        $this->assertTrue($policy->delete($user, $feeStructure));
    }

    public function test_user_without_permission_cannot_update_same_school_fee_structure(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-students');
        $feeStructure = $this->makeFeeStructure();

        $this->app['session']->put('school_id', $feeStructure->school_id);

        $policy = new FeeStructurePolicy;
        $this->assertFalse($policy->update($user, $feeStructure));
    }

    public function test_user_without_permission_cannot_delete_same_school_fee_structure(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-students');
        $feeStructure = $this->makeFeeStructure();

        $this->app['session']->put('school_id', $feeStructure->school_id);

        $policy = new FeeStructurePolicy;
        $this->assertFalse($policy->delete($user, $feeStructure));
    }

    public function test_user_with_permission_cannot_update_other_school_fee_structure(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-fee-structures');
        $schoolA = School::factory()->create();
        $feeStructure = $this->makeFeeStructure();

        $this->app['session']->put('school_id', $schoolA->id);

        $policy = new FeeStructurePolicy;
        $this->assertFalse($policy->update($user, $feeStructure));
    }

    private function makeFeeStructure(): FeeStructure
    {
        $school = School::factory()->create();
        $feeType = FeeType::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
        ]);

        return FeeStructure::create([
            'school_id' => $school->id,
            'fee_type_id' => $feeType->id,
            'amount' => 1000,
        ]);
    }
}
