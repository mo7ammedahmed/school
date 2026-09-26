<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Enums\TranslationProvider;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Services\SchoolSettingsStore;

/**
 * Translation configuration for one school.
 *
 * A school stores its own encrypted key per provider, so it can switch between
 * ChatGPT, Claude, Gemini or a custom endpoint without re-typing the others.
 * The deployment can also supply keys through the environment; a school key
 * always wins for the provider that owns it.
 */
class TranslationSettings
{
    public const KEY = 'translation';

    private readonly SchoolSettingsStore $store;

    public function __construct(private readonly int $schoolId)
    {
        $this->store = new SchoolSettingsStore($schoolId, self::KEY, self::defaults(), self::secretNames());
    }

    public static function for(School|int $school): self
    {
        return new self($school instanceof School ? (int) $school->id : $school);
    }

    /**
     * Every provider key is a separate encrypted secret.
     *
     * @return list<string>
     */
    public static function secretNames(): array
    {
        return array_map(
            static fn (TranslationProvider $provider): string => self::keyName($provider),
            TranslationProvider::cases(),
        );
    }

    public static function keyName(TranslationProvider $provider): string
    {
        return 'api_key_'.$provider->value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $provider = TranslationProvider::default();

        return [
            'provider' => $provider->value,
            'model' => $provider->defaultModel(),
            'base_url' => '',
            'auto_translate' => true,
            'source_locale' => 'en',
            'target_locale' => 'ar',
        ];
    }

    public function provider(): TranslationProvider
    {
        $value = (string) $this->store->get('provider', TranslationProvider::default()->value);

        return TranslationProvider::tryFrom($value) ?? TranslationProvider::default();
    }

    public function model(): string
    {
        return $this->store->string('model') ?? $this->provider()->defaultModel();
    }

    /**
     * A stored URL wins so a school can point at a proxy; otherwise the
     * provider's default is used. Empty means "not configured" (custom only).
     */
    public function baseUrl(): string
    {
        $stored = $this->store->string('base_url');

        if ($stored !== null) {
            return $stored;
        }

        return $this->provider()->defaultBaseUrl();
    }

    /** The URL the request will actually hit, for the settings screen. */
    public function endpoint(): string
    {
        $base = rtrim($this->baseUrl(), '/');

        return match ($this->provider()->driver()) {
            'anthropic' => $base.'/v1/messages',
            'gemini' => $base.'/v1beta/models/'.$this->model().':generateContent',
            default => $base.'/chat/completions',
        };
    }

    public function timeout(): int
    {
        return $this->provider()->timeout();
    }

    public function autoTranslate(): bool
    {
        return $this->store->bool('auto_translate', true);
    }

    public function sourceLocale(): string
    {
        $locale = $this->store->string('source_locale', 'en');

        return in_array($locale, ['en', 'ar'], true) ? (string) $locale : 'en';
    }

    public function targetLocale(): string
    {
        $locale = $this->store->string('target_locale', 'ar');

        return in_array($locale, ['en', 'ar'], true) ? (string) $locale : 'ar';
    }

    /** The key used for the active provider, school first then environment. */
    public function apiKey(): ?string
    {
        return $this->keyFor($this->provider());
    }

    public function keyFor(TranslationProvider $provider): ?string
    {
        return $this->store->decrypted(self::keyName($provider)) ?: $provider->configApiKey();
    }

    public function hasOwnKey(?TranslationProvider $provider = null): bool
    {
        return $this->store->hasSecret(self::keyName($provider ?? $this->provider()));
    }

    public function keySource(): string
    {
        if ($this->hasOwnKey()) {
            return 'school';
        }

        return $this->provider()->configApiKey() !== null ? 'environment' : 'none';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $provider = TranslationProvider::tryFrom((string) ($values['provider'] ?? '')) ?? $this->provider();

        // The screen has a single key field; it owns the slot of the provider
        // selected at save time so switching providers never overwrites another.
        if (array_key_exists('api_key', $values)) {
            $values[self::keyName($provider)] = $values['api_key'];
        }

        if (filter_var($values['clear_api_key'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $values[self::keyName($provider)] = null;
            $values['clear_'.self::keyName($provider)] = true;
        }

        unset($values['api_key'], $values['clear_api_key']);

        $this->store->save($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function masked(): array
    {
        $provider = $this->provider();

        $keys = [];
        foreach (TranslationProvider::cases() as $candidate) {
            $keys[$candidate->value] = [
                'has_school_key' => $this->hasOwnKey($candidate),
                'has_env_key' => $candidate->configApiKey() !== null,
            ];
        }

        return array_merge($this->store->masked(), [
            'provider' => $provider->value,
            'provider_label' => $provider->label(),
            'model' => $this->model(),
            'base_url' => $this->store->string('base_url') ?? '',
            'auto_translate' => $this->autoTranslate(),
            'source_locale' => $this->sourceLocale(),
            'target_locale' => $this->targetLocale(),
            'has_own_key' => $this->hasOwnKey(),
            // Never expose a key, only whether we can translate at all and
            // where the key came from.
            'has_api_key' => $this->hasOwnKey(),
            'configured' => $this->isConfigured(),
            'key_source' => $this->keySource(),
            'providers_with_keys' => $keys,
        ]);
    }
}
