<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Every absolute URL the application generates is built from the *request* host
 * unless something forces the configured origin. That makes the Host header an
 * input to link generation: an attacker sends a reset request for a victim's
 * address with `Host: evil.test`, and the victim receives a genuine mail from
 * this application whose link carries the reset token to `evil.test`.
 *
 * The password reset is the loudest example because the token is the payload.
 * The same origin builds invoice links, webhook URLs shown to operators and the
 * signed `/pay/{invoice}` URLs, so one fix covers the class.
 */
class GeneratedUrlHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'manage-schools', 'guard_name' => 'web']);
    }

    public function test_the_reset_link_is_built_from_the_configured_origin_not_the_request_host(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'victim@example.com']);

        $this->post('http://evil.test/forgot-password', ['email' => 'victim@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;
            $configuredHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

            $this->assertSame(
                $configuredHost,
                (string) parse_url($url, PHP_URL_HOST),
                "The reset mail pointed at a host the request chose: {$url}",
            );

            return true;
        });
    }

    public function test_a_relative_url_is_not_rehosted(): void
    {
        $this->get('http://evil.test/')->assertOk();

        // `url('/')` must already carry the configured origin, so the relative
        // form a template renders is absolute and cannot be rewritten by the
        // receiver. `parse_url('/')` has no `path` key at all, so the assertion
        // is on the whole URL: it has to be rooted at the configured host.
        $this->assertSame(
            (string) config('app.url'),
            rtrim((string) url('/'), '/'),
        );
    }
}
