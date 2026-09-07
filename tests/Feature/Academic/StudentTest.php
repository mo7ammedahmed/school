<?php

declare(strict_types=1);

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-students', 'guard_name' => 'web']);
    }

    public function test_student_page_loads(): void
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

        $response = $this->get('/students');
        $response->assertStatus(200);
    }

    public function test_student_can_be_created(): void
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

        $response = $this->post('/students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'nationality' => 'Saudi',
            'email' => 'john@example.com',
            'enrollment_date' => '2024-09-01',
            'status' => 'active',
        ]);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'school_id' => $school->id,
        ]);
    }

    public function test_user_cannot_access_other_school_student(): void
    {
        $user = User::factory()->create();
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $schoolA->id,
            'is_active' => true,
        ]);

        $student = Student::factory()->create([
            'school_id' => $schoolB->id,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $schoolA->id);

        $response = $this->get("/students/{$student->id}");
        $response->assertStatus(403);
    }
}
