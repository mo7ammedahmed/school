<?php

declare(strict_types=1);

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-teachers', 'guard_name' => 'web']);
    }

    public function test_teacher_page_loads(): void
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

        $response = $this->get('/teachers');
        $response->assertStatus(200);
    }

    public function test_teacher_can_be_created(): void
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

        $response = $this->post('/teachers', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '555-1234',
            'hire_date' => '2024-01-01',
            'qualification' => 'M.Ed',
            'specialization' => 'Mathematics',
        ]);

        $response->assertRedirect('/teachers');
        $this->assertDatabaseHas('teacher_profiles', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'school_id' => $school->id,
        ]);
    }
}
