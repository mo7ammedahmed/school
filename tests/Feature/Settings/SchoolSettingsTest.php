<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-schools', 'guard_name' => 'web']);
    }

    public function test_school_settings_page_loads(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-schools');
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/settings/school');
        $response->assertStatus(200);
    }

    public function test_school_settings_can_be_updated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage-schools');
        $school = School::factory()->create([
            'name_en' => 'Original School', 'name_ar' => '??????? ???????',
        ]);
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/settings/school', [
            'name_en' => 'Updated School', 'name_ar' => '??????? ???????',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'school@example.com',
        ]);

        $response->assertRedirect('/settings/school');
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name_en' => 'Updated School', 'name_ar' => '??????? ???????',
        ]);
    }
}
