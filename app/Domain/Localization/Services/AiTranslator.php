<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Enums\TranslationProvider;
use App\Domain\Localization\Exceptions\TranslationFailed;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Translates text with whichever AI provider the school selected.
 *
 * Only three dialects exist behind the five providers: OpenAI chat completions,
 * Anthropic messages, and Google generateContent. Each provider is asked to
 * return the translation alone so the response can be stored verbatim.
 */
class AiTranslator
{
    private const array LANGUAGE_NAMES = [
        'ar' => 'Arabic',
        'en' => 'English',
    ];

    /**
     * @throws TranslationFailed
     */
    public function translate(string $text, string $from, string $to, TranslationSettings $settings): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if ($from === $to) {
            return $text;
        }

        $provider = $settings->provider();

        if (! $settings->isConfigured()) {
            throw TranslationFailed::notConfigured($provider->label());
        }

        $response = $this->send($provider, $settings, $this->translationPayload($provider, $text, $from, $to, $settings));

        if ($response->failed()) {
            $message = $this->errorMessage($response);

            Log::error('Translation request was rejected', [
                'provider' => $provider->value,
                'status' => $response->status(),
                'message' => $message,
            ]);

            throw TranslationFailed::requestFailed($message);
        }

        $translated = $this->clean($this->extractText($provider, $response->json() ?? []));

        if ($translated === '') {
            throw TranslationFailed::emptyResponse();
        }

        return $translated;
    }

    /**
     * Cheap round trip used by the settings screen to prove a key works.
     *
     * @return array{ok: bool, detail: string}
     */
    public function test(TranslationSettings $settings): array
    {
        try {
            return ['ok' => true, 'detail' => $this->translate('Hello', 'en', 'ar', $settings)];
        } catch (TranslationFailed $e) {
            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /**
     * The ids the provider currently offers, so an operator can pick a live
     * model instead of one this app guessed at release time.
     *
     * @return list<string>
     *
     * @throws TranslationFailed
     */
    public function listModels(TranslationSettings $settings): array
    {
        $provider = $settings->provider();

        if (! $settings->isConfigured()) {
            throw TranslationFailed::notConfigured($provider->label());
        }

        try {
            $response = match ($provider->driver()) {
                'anthropic' => $this->client($provider, $settings)->get('/v1/models'),
                'gemini' => $this->client($provider, $settings)->get('/v1beta/models'),
                default => $this->client($provider, $settings)->get('/models'),
            };
        } catch (Throwable $e) {
            Log::error('Could not list translation models', ['provider' => $provider->value, 'error' => $e->getMessage()]);

            throw TranslationFailed::requestFailed($e->getMessage());
        }

        if ($response->failed()) {
            throw TranslationFailed::requestFailed($this->errorMessage($response));
        }

        $ids = match ($provider->driver()) {
            'gemini' => collect(Arr::get($response->json() ?? [], 'models', []))
                ->map(static fn (array $model): string => str_replace('models/', '', (string) ($model['name'] ?? '')))
                ->all(),
            default => collect(Arr::get($response->json() ?? [], 'data', []))
                ->pluck('id')
                ->all(),
        };

        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $ids,
        ))));

        sort($ids);

        return $ids;
    }

    /**
     * @throws TranslationFailed
     */
    private function send(TranslationProvider $provider, TranslationSettings $settings, array $payload): Response
    {
        $endpoint = match ($provider->driver()) {
            'anthropic' => '/v1/messages',
            'gemini' => '/v1beta/models/'.$settings->model().':generateContent',
            default => '/chat/completions',
        };

        try {
            return $this->client($provider, $settings)->post($endpoint, $payload);
        } catch (Throwable $e) {
            Log::error('Translation request failed', ['provider' => $provider->value, 'error' => $e->getMessage()]);

            throw TranslationFailed::requestFailed($e->getMessage());
        }
    }

    private function client(TranslationProvider $provider, TranslationSettings $settings): PendingRequest
    {
        $baseUrl = rtrim($settings->baseUrl(), '/');
        $key = (string) $settings->apiKey();

        $client = Http::baseUrl($baseUrl)->acceptJson()->asJson()->timeout($settings->timeout());

        return match ($provider->driver()) {
            // Anthropic authenticates with a header and pins the API version.
            'anthropic' => $client->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ]),
            // Gemini accepts the key as a header, which keeps it out of the URL.
            'gemini' => $client->withHeaders(['x-goog-api-key' => $key]),
            default => $client->withToken($key),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function translationPayload(
        TranslationProvider $provider,
        string $text,
        string $from,
        string $to,
        TranslationSettings $settings,
    ): array {
        $system = $this->systemPrompt($from, $to);

        return match ($provider->driver()) {
            'anthropic' => [
                'model' => $settings->model(),
                'max_tokens' => 1024,
                'temperature' => 0.1,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $text],
                ],
            ],
            'gemini' => [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $text]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 1024,
                ],
            ],
            default => [
                'model' => $settings->model(),
                'temperature' => 0.1,
                'top_p' => 0.9,
                'max_tokens' => 1024,
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    // Translation-specialist models on this dialect ignore the
                    // system turn and auto-detect the language, so the direction
                    // is restated in the user turn where they do read it.
                    ['role' => 'user', 'content' => $this->instructedText($text, $from, $to)],
                ],
            ],
        };
    }

    private function instructedText(string $text, string $from, string $to): string
    {
        $source = self::LANGUAGE_NAMES[$from] ?? $from;
        $target = self::LANGUAGE_NAMES[$to] ?? $to;

        return "Translate this from {$source} to {$target}:\n{$source}: {$text}\n{$target}:";
    }

    private function systemPrompt(string $from, string $to): string
    {
        $source = self::LANGUAGE_NAMES[$from] ?? $from;
        $target = self::LANGUAGE_NAMES[$to] ?? $to;

        return 'You are a professional translator for a school management system. '
            ."Translate the user's {$source} text into {$target}. "
            .'Reply with the translation only: no notes, no quotes, no transliteration and no explanation. '
            .'Keep proper nouns, numbers, codes, dates and formatting as they are. '
            .'If the text is already in '.$target.', return it unchanged.';
    }

    /**
     * Pull the assistant text out of whichever response shape came back.
     *
     * @param  array<string, mixed>  $body
     */
    private function extractText(TranslationProvider $provider, array $body): string
    {
        $text = match ($provider->driver()) {
            'anthropic' => collect(Arr::get($body, 'content', []))
                ->where('type', 'text')
                ->pluck('text')
                ->implode(''),
            'gemini' => collect(Arr::get($body, 'candidates.0.content.parts', []))
                ->pluck('text')
                ->implode(''),
            default => (string) Arr::get($body, 'choices.0.message.content', ''),
        };

        return trim($text);
    }

    private function errorMessage(Response $response): string
    {
        return (string) ($response->json('error.message')
            ?? $response->json('error.detail')
            ?? $response->json('message')
            ?? $response->json('promptFeedback.blockReason')
            ?? 'HTTP '.$response->status());
    }

    /**
     * Models sometimes wrap the answer in quotes or a "Translation:" prefix even
     * when asked not to. Strip those rather than storing them.
     */
    private function clean(string $translated): string
    {
        $translated = preg_replace('/^(translation|الترجمة)\s*[:\-]\s*/iu', '', $translated) ?? $translated;

        return trim($translated, " \t\n\r\0\x0B\"'");
    }
}
