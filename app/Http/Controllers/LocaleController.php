<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Persists the interface language.
 *
 * The client-side switcher used to keep the choice in localStorage only, so the
 * server kept rendering English names out of the bilingual columns while the
 * chrome was Arabic. This makes the choice authoritative on both sides.
 */
class LocaleController extends Controller
{
    public const LOCALES = ['en', 'ar'];

    public const COOKIE = 'locale';

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:'.implode(',', self::LOCALES)],
        ]);

        $locale = $validated['locale'];

        $request->session()->put('locale', $locale);

        // A year-long cookie so the public pages (and the next request after a
        // session expires) keep the same language.
        Cookie::queue(Cookie::make(self::COOKIE, $locale, 60 * 24 * 365));

        // Keep the signed-in user's own preference in step.
        $request->user()?->forceFill(['locale' => $locale])->save();

        return response()->json(['locale' => $locale]);
    }
}
