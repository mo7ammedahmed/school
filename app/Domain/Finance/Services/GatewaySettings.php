<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Per-school payment gateway configuration.
 *
 * Values live in `school_settings` under a single `payments` key so a school can
 * switch provider without a deploy. Secret material is encrypted at rest and is
 * never handed back to the frontend — see {@see masked()}.
 */
class GatewaySettings
{
    public const KEY = 'payments';

    public const GATEWAYS = ['offline', 'moyasar', 'hyperpay', 'stripe'];

    public const MODES = ['test', 'live'];

    public const CHANNELS = ['email', 'sms', 'inapp'];

    /** Keys that must never leave the server in plaintext. */
    private const array SECRETS = ['secret_key', 'webhook_secret'];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function __construct(private readonly int $schoolId) {}

    public static function for(School|int $school): self
    {
        return new self($school instanceof School ? (int) $school->id : $school);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'gateway' => 'offline',
            'mode' => 'test',
            'enabled' => false,
            'currency' => 'SAR',
            'auto_send' => true,
            'channels' => ['email', 'inapp'],
            'public_key' => null,
            'secret_key' => null,
            'webhook_secret' => null,
            'instructions' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->values ??= $this->load();

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function gateway(): string
    {
        $gateway = (string) $this->get('gateway', 'offline');

        return in_array($gateway, self::GATEWAYS, true) ? $gateway : 'offline';
    }

    public function mode(): string
    {
        $mode = (string) $this->get('mode', 'test');

        return in_array($mode, self::MODES, true) ? $mode : 'test';
    }

    public function enabled(): bool
    {
        return (bool) $this->get('enabled', false);
    }

    public function currency(): string
    {
        return (string) ($this->get('currency') ?: 'SAR');
    }

    public function autoSend(): bool
    {
        return (bool) $this->get('auto_send', true);
    }

    /**
     * @return list<string>
     */
    public function channels(): array
    {
        $channels = $this->get('channels', ['email', 'inapp']);

        if (! is_array($channels)) {
            return ['email', 'inapp'];
        }

        $channels = array_values(array_intersect(self::CHANNELS, $channels));

        return $channels === [] ? ['email', 'inapp'] : $channels;
    }

    public function wantsChannel(string $channel): bool
    {
        return in_array($channel, $this->channels(), true);
    }

    public function publicKey(): ?string
    {
        $value = $this->get('public_key');

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function secretKey(): ?string
    {
        return $this->reveal('secret_key');
    }

    public function webhookSecret(): ?string
    {
        return $this->reveal('webhook_secret');
    }

    public function instructions(): ?string
    {
        $value = $this->get('instructions');

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * A real online checkout is only possible when the provider is switched on
     * and both credentials are present.
     */
    public function supportsOnlineCheckout(): bool
    {
        return $this->enabled()
            && $this->gateway() !== 'offline'
            && $this->publicKey() !== null
            && $this->secretKey() !== null;
    }

    public function label(): string
    {
        return match ($this->gateway()) {
            'moyasar' => 'Moyasar',
            'hyperpay' => 'HyperPay',
            'stripe' => 'Stripe',
            default => 'Offline / bank transfer',
        };
    }

    /**
     * Safe representation for the settings screen: secrets are replaced by a
     * boolean so an operator can tell one is stored without reading it.
     *
     * @return array<string, mixed>
     */
    public function masked(): array
    {
        return [
            'gateway' => $this->gateway(),
            'mode' => $this->mode(),
            'enabled' => $this->enabled(),
            'currency' => $this->currency(),
            'auto_send' => $this->autoSend(),
            'channels' => $this->channels(),
            'public_key' => $this->publicKey(),
            'has_secret_key' => $this->secretKey() !== null,
            'has_webhook_secret' => $this->webhookSecret() !== null,
            'instructions' => $this->instructions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $current = $this->all();

        foreach (self::SECRETS as $secret) {
            // The form never round-trips a secret back to the browser, so a blank
            // submission means "leave the stored value alone". An explicit
            // clear_secret_key flag is how an operator removes one.
            if ($this->shouldClear($values, $secret)) {
                $values[$secret] = null;
                $values['clear_'.$secret] = false;

                continue;
            }

            unset($values['clear_'.$secret]);

            if (! array_key_exists($secret, $values) || $values[$secret] === '' || $values[$secret] === null) {
                $values[$secret] = $current[$secret] ?? null;

                continue;
            }

            $values[$secret] = Crypt::encryptString((string) $values[$secret]);
        }

        $merged = array_merge($current, $values);
        $merged['channels'] = array_values(array_intersect(self::CHANNELS, (array) ($merged['channels'] ?? [])));
        $merged['enabled'] = (bool) ($merged['enabled'] ?? false);
        $merged['auto_send'] = (bool) ($merged['auto_send'] ?? false);

        // The setting is this school's own row, so it is written inside that
        // school's context: the tenant scope is live on SchoolSetting, and a
        // settings save can happen outside a request (a command, a seeder).
        app(TenantContext::class)->runFor($this->schoolId, fn () => SchoolSetting::updateOrCreate(
            ['school_id' => $this->schoolId, 'key' => self::KEY],
            ['value' => json_encode($merged), 'type' => 'json'],
        ));

        $this->values = $merged;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        // Pinned rather than unscoped: the row wanted is this school's, and a
        // context-free read must not become a cross-school read.
        $setting = app(TenantContext::class)->runFor($this->schoolId, fn () => SchoolSetting::query()
            ->where('school_id', $this->schoolId)
            ->where('key', self::KEY)
            ->first());

        if ($setting === null || $setting->value === null || $setting->value === '') {
            return self::defaults();
        }

        $decoded = json_decode((string) $setting->value, true);

        if (! is_array($decoded)) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $decoded);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function shouldClear(array $values, string $secret): bool
    {
        return filter_var($values['clear_'.$secret] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function reveal(string $key): ?string
    {
        $stored = $this->get($key);

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            $plain = Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            // Value predates encryption or the app key rotated; treat as unset
            // rather than leaking a ciphertext into a gateway request.
            return null;
        }

        return $plain === '' ? null : $plain;
    }

    /**
     * Record gateway traffic for the settings "logs" tab.
     *
     * @return array<string, int>
     */
    public function activity(): array
    {
        return [
            'transactions' => (int) DB::table('gateway_transactions')
                ->where('school_id', $this->schoolId)
                ->count(),
            'completed' => (int) DB::table('gateway_transactions')
                ->where('school_id', $this->schoolId)
                ->where('status', 'completed')
                ->count(),
            'webhook_events' => (int) DB::table('webhook_events')
                ->where('school_id', $this->schoolId)
                ->count(),
            'failed_webhooks' => (int) DB::table('webhook_events')
                ->where('school_id', $this->schoolId)
                ->where('status', 'failed')
                ->count(),
        ];
    }
}
