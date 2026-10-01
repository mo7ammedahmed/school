<?php

declare(strict_types=1);

namespace App\Domain\Schools\Services;

use App\Domain\Schools\Models\SchoolSetting;
use App\Domain\Schools\Support\TenantContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * A small JSON blob of settings per school, stored in `school_settings`.
 *
 * Every integration (payments, SMS, translation) needs the same three things:
 * persisted values with defaults, secrets that are encrypted at rest, and a
 * masked view that is safe to hand to the browser. This class owns that logic
 * once so the integrations do not each re-implement it.
 */
class SchoolSettingsStore
{
    /** @var array<string, mixed>|null */
    private ?array $values = null;

    /**
     * @param  array<string, mixed>  $defaults
     * @param  list<string>  $secrets  keys that are encrypted at rest
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly string $key,
        private readonly array $defaults = [],
        private readonly array $secrets = [],
    ) {}

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

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key, $default);

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function decrypted(string $key): ?string
    {
        $stored = $this->get($key);

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            // The value predates encryption, or APP_KEY rotated. Treat as unset
            // rather than handing ciphertext to an integration.
            return null;
        }
    }

    public function hasSecret(string $key): bool
    {
        $value = $this->get($key);

        return $value !== null && $value !== '';
    }

    /**
     * Merge values in. Secret fields follow three rules:
     *  - blank or absent  => keep whatever is stored
     *  - `clear_<secret>` => remove the stored secret
     *  - anything else    => encrypt and replace
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $current = $this->all();

        foreach ($this->secrets as $secret) {
            $flag = 'clear_'.$secret;

            if (filter_var($values[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $values[$secret] = null;

                continue;
            }

            if (! array_key_exists($secret, $values) || $values[$secret] === '' || $values[$secret] === null) {
                $values[$secret] = $current[$secret] ?? null;

                continue;
            }

            $values[$secret] = Crypt::encryptString((string) $values[$secret]);
        }

        $merged = array_merge($current, $values);

        // This store is built with a school id, so it pins that school while it
        // touches the tenant-scoped settings table — it is also used from
        // commands and tests, where no request session exists.
        app(TenantContext::class)->runFor($this->schoolId, fn () => SchoolSetting::updateOrCreate(
            ['school_id' => $this->schoolId, 'key' => $this->key],
            ['value' => json_encode($merged), 'type' => 'json'],
        ));

        $this->values = $merged;
    }

    /**
     * The value of a secret is replaced by a boolean so an operator can tell one
     * is stored without being able to read it.
     *
     * @param  list<string>  $extraSecrets
     * @return array<string, mixed>
     */
    public function masked(array $extraSecrets = []): array
    {
        $masked = $this->all();

        foreach (array_merge($this->secrets, $extraSecrets) as $secret) {
            $masked['has_'.$secret] = $this->hasSecret($secret);
            unset($masked[$secret]);
        }

        return $masked;
    }

    /**
     * Force a single value to null without an explicit clear flag.
     */
    public function clear(string $key): void
    {
        $this->save([$key => null, 'clear_'.$key => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $setting = app(TenantContext::class)->runFor($this->schoolId, fn () => SchoolSetting::query()
            ->where('school_id', $this->schoolId)
            ->where('key', $this->key)
            ->first());

        if ($setting === null || $setting->value === null || $setting->value === '') {
            return $this->defaults;
        }

        $decoded = json_decode((string) $setting->value, true);

        return is_array($decoded) ? array_merge($this->defaults, $decoded) : $this->defaults;
    }
}
