<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Models\InterfaceTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The dashboard's curated interface dictionary, in one piece.
 *
 * The screens are written in English, so choosing Arabic used to mean the
 * browser asking the school's provider to translate whatever happened to be on
 * screen: six visible strings at a time, one provider call each, in the middle
 * of the page load. A page therefore arrived in English and slowly turned
 * Arabic, and the words that were never reached stayed English for good.
 *
 * Shipping the whole dictionary with the page instead costs one cached read:
 * the browser paints the Arabic it already knows, asks the provider only for
 * what is genuinely new, and can do that after the page is on screen.
 */
class InterfaceCatalog
{
    /** Cookie carrying the version the browser already holds. */
    public const COOKIE = 'ui_copy_version';

    /** A day is short enough that an edit shows up, and long enough to pay off. */
    private const TTL = 86400;

    /**
     * Every pair, keyed by the English string a screen renders.
     *
     * Cached against the catalog's own version, so editing a translation
     * invalidates the entry rather than serving the old wording for a day.
     *
     * @return array<string, string>
     */
    public function translations(): array
    {
        return Cache::remember(
            'ui-copy:catalog:'.$this->version(),
            self::TTL,
            static fn (): array => InterfaceTranslation::query()
                ->orderBy('id')
                ->pluck('arabic', 'english')
                ->all(),
        );
    }

    /** Changes whenever any translation is added, edited or removed. */
    public function version(): string
    {
        return InterfaceTranslation::catalogVersion();
    }

    /**
     * @return array{version: string, translations: array<string, string>}
     */
    public function payload(): array
    {
        return [
            'version' => $this->version(),
            'translations' => $this->translations(),
        ];
    }

    /**
     * Whether this request still needs the dictionary sent to it.
     *
     * Only Arabic screens render the dashboard chrome the dictionary covers, and
     * only a signed-in user has it — a visitor reading the public website is
     * handed copy that is already translated at the source.
     *
     * The version cookie is what keeps the payload off every later navigation:
     * once the browser has the dictionary it is asked for nothing more, and a
     * cleared cache falls back to the catalog endpoint.
     */
    public function shouldShare(Request $request): bool
    {
        if ($request->user() === null || app()->getLocale() !== 'ar') {
            return false;
        }

        return $request->cookie(self::COOKIE) !== $this->version();
    }
}
