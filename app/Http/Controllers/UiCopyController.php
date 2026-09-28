<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Localization\Models\InterfaceTranslation;
use App\Domain\Localization\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Translates the app's own interface words.
 *
 * The dashboard was written in English — hundreds of pages of headings, labels
 * and buttons — so choosing Arabic left English chrome wrapped around Arabic
 * content. Re-typing every screen by hand is the better long-term answer, but
 * this endpoint is what makes the choice work everywhere today: the browser
 * sends the visible interface strings, the school's own translation provider
 * answers once per string, and {@see TranslationService} caches the result so a
 * page costs nothing after its first view.
 *
 * Only interface text arrives here (the client sends headings, labels, buttons
 * and table headers, never table data), and a batch gets a clock just like the
 * content sweep, so a slow provider can never take the request down with it.
 */
class UiCopyController extends Controller
{
    /** Strings one request may translate before it hands the rest back. */
    private const int BATCH = 200;

    /** Per user, per minute. */
    private const int ATTEMPTS = 40;

    /** How long a batch may spend talking to the provider. */
    private const float SECONDS = 8.0;

    /**
     * The longest string that counts as interface copy. It matches the client
     * (see `ui-copy.ts`), so the browser never sends something this endpoint
     * would reject — one rejected string would fail its whole slice.
     */
    private const int MAX_STRING = 400;

    public function __construct(private readonly TranslationService $translations) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Blanks are dropped before validation: the middleware turns an empty
        // string into null, and a page that has nothing to translate is not an
        // error worth a 422 in the browser console.
        $request->merge([
            'strings' => array_values(array_filter(
                (array) $request->input('strings', []),
                static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
            )),
        ]);

        $validated = $request->validate([
            'strings' => ['present', 'array', 'max:'.self::BATCH],
            'strings.*' => ['string', 'max:'.self::MAX_STRING],
        ]);

        $strings = array_values(array_unique(array_map('trim', $validated['strings'])));
        $hashes = array_map(InterfaceTranslation::hashSource(...), $strings);
        $savedEntries = InterfaceTranslation::query()
            ->whereIn('source_hash', $hashes)
            ->get(['source_hash', 'english', 'arabic']);
        /** @var array<string, InterfaceTranslation> $savedTranslations */
        $savedTranslations = [];

        foreach ($savedEntries as $entry) {
            $savedTranslations[$entry->source_hash] = $entry;
        }

        $translations = [];

        foreach ($strings as $english) {
            $entry = $savedTranslations[InterfaceTranslation::hashSource($english)] ?? null;

            if ($entry !== null) {
                $translations[$english] = $entry->arabic;
            }
        }

        $missing = array_values(array_diff($strings, array_keys($translations)));
        $schoolId = (int) session('school_id');
        $configured = $schoolId !== 0 && $this->translations->autoTranslateEnabled($schoolId);
        $isSuperAdmin = $request->user()?->hasRole('super_admin') ?? false;

        // Curated strings are global and available even when a school has no AI key.
        if ($missing === [] || ! $configured) {
            return response()->json([
                'translations' => $translations,
                'pending' => [],
                'configured' => $configured,
            ]);
        }

        $key = 'ui-copy:'.($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            return response()->json([
                'translations' => $translations,
                'pending' => $missing,
                'message' => 'Too many translation requests in a row. The page will finish translating in a moment.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $started = microtime(true);
        $limit = (int) ini_get('max_execution_time');
        $mustNotFinishAfter = $limit > 0 ? $started + $limit - 2.0 : PHP_FLOAT_MAX;

        $pending = [];
        $rateLimited = false;

        foreach ($missing as $index => $english) {
            // Stop asking for more once the batch has spent its share of the
            // request; the screen repeats with whatever is left.
            if (microtime(true) - $started >= self::SECONDS || microtime(true) + 10.0 > $mustNotFinishAfter) {
                $pending[] = $english;

                continue;
            }

            $failureStatus = null;
            $arabic = $this->translations->translateQuietly($english, 'en', 'ar', $schoolId, $failureStatus);

            if ($arabic === null) {
                if ($failureStatus === 429) {
                    $pending = array_merge($pending, array_slice($missing, $index));
                    $rateLimited = true;
                    break;
                }

                continue;
            }

            if ($arabic === '' || $arabic === $english) {
                // Not a failure worth reporting: the string may be a proper noun
                // the provider returns unchanged, and the English stays readable.
                continue;
            }

            $translations[$english] = $arabic;

            if ($isSuperAdmin) {
                InterfaceTranslation::query()->firstOrCreate(
                    ['source_hash' => InterfaceTranslation::hashSource($english)],
                    [
                        'english' => $english,
                        'arabic' => $arabic,
                        'updated_by' => $request->user()->id,
                    ],
                );
            }
        }

        if ($rateLimited) {
            return response()->json([
                'translations' => $translations,
                'pending' => $pending,
                'configured' => true,
                'message' => 'The translation provider rate limit was reached (HTTP 429). Wait before retrying or check your provider quota.',
            ], 429);
        }

        return response()->json([
            'translations' => $translations,
            'pending' => $pending,
            'configured' => true,
        ]);
    }

    public function version(): JsonResponse
    {
        return response()->json(['version' => InterfaceTranslation::catalogVersion()]);
    }
}
