<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Identity\Services\TotpService;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * None of the authentication routes were limited: a password could be guessed
 * as fast as the server answered, a reset link could be requested forever, and
 * a six-digit second factor could be brute-forced ten thousand codes a second.
 *
 * The limit is five attempts per minute per email-and-address, and it is the
 * sixth request that must be refused even when it is correct — otherwise the
 * counter is decoration.
 */
class AuthThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        User::factory()->create([
            'email' => 'lock@example.test',
            'password' => bcrypt('password'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'lock@example.test', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'lock@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many attempts', (string) session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_a_sign_in_inside_the_limit_still_works(): void
    {
        $user = User::factory()->create([
            'email' => 'clear@example.test',
            'password' => bcrypt('password'),
        ]);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/login', ['email' => 'clear@example.test', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => 'clear@example.test', 'password' => 'password'])
            ->assertRedirect('/select-school');

        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'reset@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/forgot-password', ['email' => 'reset@example.test'])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'reset@example.test'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many attempts', (string) session('errors')->first('email'));
        // Laravel's own token repository only creates one token per minute, so
        // the first request mails and the rest are answered as sent without
        // mailing again. The point here is our limiter, which refuses the sixth.
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_resetting_a_password_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-link@example.test',
            'password' => bcrypt('password'),
        ]);

        $token = Password::broker()->createToken($user);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/reset-password/'.$token, [
                'token' => 'not-the-token',
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/reset-password/'.$token, [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many attempts', (string) session('errors')->first('email'));
        $this->assertFalse(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_the_two_factor_challenge_is_rate_limited(): void
    {
        $totp = $this->app->make(TotpService::class);

        $user = User::factory()->create([
            'email' => 'challenge@example.test',
            'password' => bcrypt('password'),
        ]);

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => $totp->generateSecret(),
        ])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        }

        // Even the right code is refused while the limiter holds.
        $this->post('/two-factor-challenge', ['code' => $totp->code($user->two_factor_secret)])
            ->assertSessionHasErrors('code');

        $this->assertStringContainsString('Too many attempts', (string) session('errors')->first('code'));
        $this->assertGuest();
    }
}
