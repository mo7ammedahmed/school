<?php

declare(strict_types=1);

namespace App\Domain\Localization\Enums;

use App\Domain\Localization\Services\AiTranslator;

/**
 * The AI services translation can run through.
 *
 * Each provider only has to describe *what it is* — endpoint, auth header and
 * model ids. {@see AiTranslator} turns that
 * description into the right HTTP call, so adding a provider never means
 * touching the translation pipeline.
 */
enum TranslationProvider: string
{
    case Nvidia = 'nvidia';
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';

    /** Any other OpenAI-compatible endpoint (Azure, OpenRouter, a local vLLM…). */
    case Custom = 'custom';

    /** The request/response dialect this provider speaks. */
    public function driver(): string
    {
        return match ($this) {
            self::Anthropic => 'anthropic',
            self::Gemini => 'gemini',
            default => 'openai',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Nvidia => 'NVIDIA NIM',
            self::OpenAi => 'OpenAI (ChatGPT)',
            self::Anthropic => 'Anthropic (Claude)',
            self::Gemini => 'Google Gemini',
            self::Custom => 'OpenAI-compatible (custom)',
        };
    }

    /** Where an operator gets a key, shown as a hint on the settings screen. */
    public function helpUrl(): string
    {
        return match ($this) {
            self::Nvidia => 'https://build.nvidia.com',
            self::OpenAi => 'https://platform.openai.com/api-keys',
            self::Anthropic => 'https://console.anthropic.com/settings/keys',
            self::Gemini => 'https://aistudio.google.com/app/apikey',
            self::Custom => '',
        };
    }

    public function keyPlaceholder(): string
    {
        return match ($this) {
            self::Nvidia => 'nvapi-…',
            self::OpenAi => 'sk-…',
            self::Anthropic => 'sk-ant-…',
            self::Gemini => 'AIza…',
            self::Custom => 'your-api-key',
        };
    }

    /** A custom endpoint has no sane default, so the operator must supply one. */
    public function requiresBaseUrl(): bool
    {
        return $this === self::Custom;
    }

    public function defaultBaseUrl(): string
    {
        $configured = $this->configString('base_url');

        return $configured ?? match ($this) {
            self::Nvidia => 'https://integrate.api.nvidia.com/v1',
            self::OpenAi => 'https://api.openai.com/v1',
            self::Anthropic => 'https://api.anthropic.com',
            self::Gemini => 'https://generativelanguage.googleapis.com',
            self::Custom => '',
        };
    }

    public function defaultModel(): string
    {
        return $this->configString('model') ?? '';
    }

    /**
     * Suggestions only — the screen lets an operator type any id because
     * providers retire model ids far more often than this app ships releases.
     *
     * @return list<string>
     */
    public function suggestedModels(): array
    {
        return match ($this) {
            // Which ids a NIM account may call varies; these are the ones the
            // hosted catalog exposes and an operator can swap freely.
            self::Nvidia => [
                'nvidia/riva-translate-4b-instruct-v2',
                'nvidia/nemotron-3-super-120b-a12b',
                'z-ai/glm-5.3-flash',
                'meta/llama-3.1-8b-instruct',
            ],
            self::OpenAi => ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini'],
            self::Anthropic => ['claude-3-5-haiku-latest', 'claude-3-5-sonnet-latest'],
            self::Gemini => ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash'],
            self::Custom => [],
        };
    }

    /** The environment variable a deployment can set instead of a school key. */
    public function envKeyName(): string
    {
        return match ($this) {
            self::Nvidia => 'NVIDIA_API_KEY',
            self::OpenAi => 'OPENAI_API_KEY',
            self::Anthropic => 'ANTHROPIC_API_KEY',
            self::Gemini => 'GEMINI_API_KEY',
            self::Custom => 'CUSTOM_TRANSLATION_API_KEY',
        };
    }

    /** null for the custom provider, which has no config block. */
    public function configPath(): ?string
    {
        return $this === self::Custom ? null : 'services.'.$this->value;
    }

    public function configApiKey(): ?string
    {
        return $this->configString('api_key');
    }

    public function timeout(): int
    {
        $path = $this->configPath();

        if ($path === null) {
            return 45;
        }

        $timeout = config($path.'.timeout');

        return is_numeric($timeout) && (int) $timeout > 0 ? (int) $timeout : 45;
    }

    public function clientId(): string
    {
        return $this->value;
    }

    /**
     * Shape handed to the settings screen.
     *
     * @return array{value: string, label: string, driver: string, models: list<string>, default_model: string, default_base_url: string, requires_base_url: bool, key_placeholder: string, help_url: string}
     */
    public function toOption(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'driver' => $this->driver(),
            'models' => $this->suggestedModels(),
            'default_model' => $this->defaultModel(),
            'default_base_url' => $this->defaultBaseUrl(),
            'requires_base_url' => $this->requiresBaseUrl(),
            'key_placeholder' => $this->keyPlaceholder(),
            'help_url' => $this->helpUrl(),
        ];
    }

    /**
     * @return list<array{value: string, label: string, driver: string, models: list<string>, default_model: string, default_base_url: string, requires_base_url: bool, key_placeholder: string, help_url: string}>
     */
    public static function options(): array
    {
        return array_map(static fn (self $provider): array => $provider->toOption(), self::cases());
    }

    public static function default(): self
    {
        $configured = (string) config('services.translation.provider', self::Nvidia->value);

        return self::tryFrom($configured) ?? self::Nvidia;
    }

    private function configString(string $key): ?string
    {
        $path = $this->configPath();

        if ($path === null) {
            return null;
        }

        $value = config($path.'.'.$key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
