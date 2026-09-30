<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Identity\Services\RecoveryCodes;
use App\Domain\Identity\Services\TotpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Two-factor authentication that anyone can skip is decoration.
 *
 * The account below has a confirmed secret, and a password alone used to sign
 * it in: the login controller never looked at `two_factor_enabled`, and the
 * challenge screen read `$request->user()` while sitting in the guest group, so
 * it could only ever answer a user who was already signed in — the one state it
 * was supposed to prevent. The `auth.two_factor_confirmed` flag it set was read
 * by nothing.
 *
 * These cases are the contract: a password starts a challenge, not a session,
 * and only a valid code (or a recovery code, once) finishes it.
 */
class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = $this->app->make(TotpService::class);
    }

    public function test_a_password_alone_does_not_sign_in_a_two_factor_account(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/two-factor-challenge');

        $this->assertGuest();
    }

    public function test_the_dashboard_is_out_of_reach_until_the_code_is_given(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_valid_code_completes_the_sign_in(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->post('/two-factor-challenge', ['code' => $this->totp->code($user->two_factor_secret)])
            ->assertRedirect('/select-school');

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('auth.two_factor_user_id'), 'The pending sign-in must not survive the challenge.');
    }

    public function test_an_invalid_code_does_not_sign_in(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_recovery_code_signs_in_once_and_is_then_spent(): void
    {
        $user = $this->twoFactorUser();
        $codes = $this->totp->recoveryCodes(2);

        $user->forceFill([
            'two_factor_recovery_codes' => $this->app->make(RecoveryCodes::class)->hashAll($codes),
        ])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $codes[0]])->assertRedirect('/select-school');
        $this->assertAuthenticatedAs($user);

        Auth::logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $codes[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_the_challenge_sends_a_visitor_with_no_pending_sign_in_to_the_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');
        $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect('/login');
    }

    public function test_a_password_sign_in_without_two_factor_is_unchanged(): void
    {
        $user = User::factory()->create([
            'email' => 'plain@example.test',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', ['email' => 'plain@example.test', 'password' => 'password'])
            ->assertRedirect('/select-school');

        $this->assertAuthenticatedAs($user);
    }

    private function twoFactorUser(): User
    {
        $user = User::factory()->create([
            'email' => 'twofactor@example.test',
            'password' => bcrypt('password'),
        ]);

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => $this->totp->generateSecret(),
        ])->save();

        return $user;
    }
}
