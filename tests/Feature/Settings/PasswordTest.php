<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Domain\Schools\Models\School;
use App\Domain\Identity\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-schools', 'guard_name' => 'web']);
    }

    public function test_password_page_loads(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/settings/password');
        $response->assertStatus(200);
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/settings/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/settings/password');
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
