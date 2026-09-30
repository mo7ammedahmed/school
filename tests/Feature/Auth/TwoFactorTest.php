<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The page used to answer every visitor — including one with no sign-in in
     * progress — because its controller read `$request->user()`, which is null
     * in the guest group, and then did nothing with it. The enforcement cases
     * live in {@see TwoFactorEnforcementTest}; this one pins the state the page
     * *is* for.
     */
    public function test_two_factor_challenge_page_loads_for_a_pending_sign_in(): void
    {
        $user = User::factory()->create();

        $this->withSession(['auth.two_factor_user_id' => $user->id]);

        $this->get('/two-factor-challenge')->assertStatus(200);
    }
}
