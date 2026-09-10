<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\People\Models\Student;
use App\Domain\People\Policies\StudentPolicy;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-students', 'guard_name' => 'web']);
    }

    public function test_user_with_permission_can_view_student(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-students');
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $school->id,
        ]);

        $policy = new StudentPolicy;
        $this->assertTrue($policy->view($user, $student));
    }

    public function test_user_without_permission_cannot_view_other_school_student(): void
    {
        $user = User::factory()->create();
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $schoolB->id,
        ]);

        $this->app['session']->put('school_id', $schoolA->id);

        $policy = new StudentPolicy;
        $this->assertFalse($policy->view($user, $student));
    }

    public function test_user_cannot_delete_graduated_student(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-students');
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'status' => 'graduated',
        ]);

        $policy = new StudentPolicy;
        $this->assertFalse($policy->delete($user, $student));
    }
}
