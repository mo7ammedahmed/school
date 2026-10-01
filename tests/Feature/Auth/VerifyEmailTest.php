<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The notice had two defects at once: it named a controller method nobody had
 * written, so it was a 500 for everyone, and it sat in the `guest` group — so
 * the signed-in, unverified user it exists for was answered with a redirect to
 * the dashboard instead.
 */
class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notice_answers_a_signed_in_user_who_has_not_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertStatus(200);
    }

    public function test_a_guest_is_sent_to_the_login(): void
    {
        $this->get('/verify-email')->assertRedirect('/login');
    }
}
