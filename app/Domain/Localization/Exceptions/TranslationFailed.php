<?php

declare(strict_types=1);

namespace App\Domain\Localization\Exceptions;

use RuntimeException;

/**
 * Raised when a translation could not be produced. Callers treat this as a
 * soft failure: a record still saves, it just does not gain a translation.
 */
class TranslationFailed extends RuntimeException
{
    public static function notConfigured(?string $provider = null): self
    {
        $label = $provider !== null && $provider !== '' ? $provider : 'AI';

        return new self("No {$label} API key is configured for translation.");
    }

    public static function requestFailed(string $detail): self
    {
        return new self('The translation service could not be reached: '.$detail);
    }

    public static function emptyResponse(): self
    {
        return new self('The translation service returned an empty result.');
    }
}
