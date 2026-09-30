<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A session is a bearer token, and two moments must not leave the old one
 * valid: changing a password, and changing which school the session speaks for.
 *
 * The session middleware keeps a copy of the password hash and signs a device
 * out when the two stop agreeing; nothing enabled it, so a stolen session
 * survived the password change that was supposed to revoke it.
 */
class SessionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_the_password_signs_out_other_sessions(): void
    {
        $school = School::factory()->create();
        $user = $this->actingAsSchoolUser($school, [], ['password' => 'old-password-123']);

        $this->get('/settings/password');
        $staleHash = session('password_hash_web');
        $this->assertNotNull($staleHash, 'The session middleware did not record the password hash.');

        $this->post('/settings/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertRedirect('/settings/password');

        $this->assertTrue(Hash::check('new-password-456', $user->refresh()->password));

        // The device that changed the password stays signed in ...
        $this->get('/settings/password')->assertOk();

        // ... while a session still carrying the old hash is signed out.
        $this->withSession(['password_hash_web' => $staleHash]);
        $this->get('/settings/password')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_switching_school_regenerates_the_session(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $before = session()->getId();

        $this->post('/select-school', ['school_id' => $school->id])
            ->assertRedirect('/dashboard');

        $this->assertNotSame($before, session()->getId(), 'Selecting a school must start a new session id.');
    }
}
