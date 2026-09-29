<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_payment_settings_page_loads_for_a_school_admin(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->get('/settings/payments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/payments')
                ->where('settings.gateway', 'offline')
                ->where('settings.auto_send', true)
                ->has('gateways')
                ->has('channels')
            );
    }

    public function test_a_user_without_settings_permission_cannot_open_payment_settings(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $this->get('/settings/payments')->assertForbidden();
    }

    public function test_saving_gateway_settings_persists_them(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/payments', [
            'gateway' => 'moyasar',
            'mode' => 'live',
            'enabled' => true,
            'currency' => 'SAR',
            'auto_send' => false,
            'channels' => ['email', 'sms'],
            'public_key' => 'pk_test_123',
            'secret_key' => 'sk_test_abc',
            'webhook_secret' => 'whsec_xyz',
            'instructions' => 'IBAN SA00 0000 0000',
        ])->assertRedirect();

        $settings = GatewaySettings::for($school->id);

        $this->assertSame('moyasar', $settings->gateway());
        $this->assertSame('live', $settings->mode());
        $this->assertTrue($settings->enabled());
        $this->assertFalse($settings->autoSend());
        $this->assertSame(['email', 'sms'], $settings->channels());
        $this->assertSame('sk_test_abc', $settings->secretKey());
        $this->assertSame('whsec_xyz', $settings->webhookSecret());
        $this->assertTrue($settings->supportsOnlineCheckout());
    }

    public function test_secrets_are_stored_encrypted_and_never_exposed_to_the_browser(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->post('/settings/payments', [
            'gateway' => 'stripe',
            'mode' => 'test',
            'enabled' => true,
            'currency' => 'SAR',
            'auto_send' => true,
            'channels' => ['email'],
            'public_key' => 'pk_live_abc',
            'secret_key' => 'sk_live_supersecret',
            'webhook_secret' => '',
            'instructions' => '',
        ])->assertRedirect();

        $raw = (string) SchoolSetting::where('school_id', $school->id)
            ->where('key', GatewaySettings::KEY)
            ->value('value');

        $this->assertStringNotContainsString('sk_live_supersecret', $raw, 'the secret must not be stored in the clear');
        $this->assertSame('sk_live_supersecret', GatewaySettings::for($school->id)->secretKey());

        $masked = GatewaySettings::for($school->id)->masked();
        $this->assertTrue($masked['has_secret_key']);
        $this->assertArrayNotHasKey('secret_key', $masked);
        $this->assertStringNotContainsString('sk_live_supersecret', json_encode($masked));
    }

    public function test_leaving_the_secret_blank_keeps_the_stored_one(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        GatewaySettings::for($school->id)->save([
            'gateway' => 'moyasar',
            'enabled' => true,
            'public_key' => 'pk_1',
            'secret_key' => 'sk_original',
        ]);

        $this->post('/settings/payments', [
            'gateway' => 'moyasar',
            'mode' => 'test',
            'enabled' => true,
            'currency' => 'SAR',
            'auto_send' => true,
            'channels' => ['email'],
            'public_key' => 'pk_2',
            'secret_key' => '',
            'webhook_secret' => '',
            'instructions' => '',
        ])->assertRedirect();

        $settings = GatewaySettings::for($school->id);

        $this->assertSame('sk_original', $settings->secretKey());
        $this->assertSame('pk_2', $settings->publicKey());
    }

    public function test_an_operator_can_remove_a_stored_secret(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        GatewaySettings::for($school->id)->save([
            'gateway' => 'moyasar',
            'enabled' => true,
            'public_key' => 'pk_1',
            'secret_key' => 'sk_original',
        ]);

        $this->post('/settings/payments', [
            'gateway' => 'moyasar',
            'mode' => 'test',
            'enabled' => true,
            'currency' => 'SAR',
            'auto_send' => true,
            'channels' => ['email'],
            'public_key' => 'pk_1',
            'secret_key' => '',
            'webhook_secret' => '',
            'clear_secret_key' => true,
            'instructions' => '',
        ])->assertRedirect();

        $settings = GatewaySettings::for($school->id);

        $this->assertNull($settings->secretKey());
        $this->assertFalse($settings->masked()['has_secret_key']);
        // Without a secret the gateway can no longer take card payments.
        $this->assertFalse($settings->supportsOnlineCheckout());
    }

    public function test_the_gateway_logs_page_loads(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-settings']);

        $this->get('/settings/payments/logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/payments-logs')
                ->has('transactions')
                ->has('events')
            );
    }
}
