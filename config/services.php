<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI translation providers
    |--------------------------------------------------------------------------
    |
    | Translation can run through any of these providers. A school overrides the
    | key, model and (for a custom OpenAI-compatible endpoint) the base URL from
    | Settings -> Translations; these are the deployment-wide defaults.
    |
    | Model ids come and go — providers retire them — so the settings screen
    | lets an operator type any id or pull the live list from the provider.
    |
    | The default provider for a fresh install.
    |
    */
    'translation' => [
        'provider' => env('TRANSLATION_PROVIDER', 'nvidia'),
    ],

    // OpenAI-compatible: OpenAI and NVIDIA NIM share this request shape.
    'nvidia' => [
        'api_key' => env('NVIDIA_API_KEY'),
        'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'model' => env('NVIDIA_MODEL', 'nvidia/riva-translate-4b-instruct-v2'),
        'timeout' => (int) env('NVIDIA_TIMEOUT', 45),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 45),
    ],

    // Anthropic uses /v1/messages with an x-api-key header.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-latest'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 45),
    ],

    // Google Generative Language API: /v1beta/models/{model}:generateContent.
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
    ],

];
