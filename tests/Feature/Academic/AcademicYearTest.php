<?php

declare(strict_types=1);

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Academics\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-academic-years', 'guard_name' => 'web']);
    }

    public function test_academic_year_page_loads(): void
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

        $response = $this->get('/academic-years');
        $response->assertStatus(200);
    }

    public function test_academic_year_can_be_created(): void
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

        $response = $this->post('/academic-years', [
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => true,
        ]);

        $response->assertRedirect('/academic-years');
        $this->assertDatabaseHas('academic_years', [
            'name' => '2024-2025',
            'school_id' => $school->id,
        ]);
    }

    public function test_user_cannot_access_other_school_academic_year(): void
    {
        $user = User::factory()->create();
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $schoolA->id,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::factory()->create([
            'school_id' => $schoolB->id,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $schoolA->id);

        $response = $this->get("/academic-years/{$academicYear->id}");
        $response->assertStatus(403);
    }
}
