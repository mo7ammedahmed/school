<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage-schools', 'guard_name' => 'web']);
    }

    public function test_reset_password_page_loads(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->get('/reset-password/'.$token.'?email='.urlencode($user->email));
        $response->assertStatus(200);
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password/'.$token, [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(password_verify('NewPassword123!', $user->fresh()->password));
    }
}
