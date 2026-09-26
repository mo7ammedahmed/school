<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Localization\Exceptions\TranslationFailed;
use App\Domain\Localization\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Backs the "Translate" button next to every bilingual field.
 *
 * Any signed-in user who can create records needs this, so it only requires
 * authentication; it is rate limited because each call spends money at the
 * translation provider.
 */
class TranslateController extends Controller
{
    /** Per user, per minute. */
    private const int ATTEMPTS = 30;

    public function __construct(private readonly TranslationService $translations) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'from' => ['nullable', 'in:en,ar'],
            'to' => ['nullable', 'in:en,ar'],
            'school_id' => ['nullable', 'integer'],
        ]);

        $from = $validated['from'] ?? $this->translations->settings()->sourceLocale();
        $to = $validated['to'] ?? $this->translations->settings()->targetLocale();

        if ($from === $to) {
            return response()->json(['translation' => $validated['text']]);
        }

        $key = 'translate:'.($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            return response()->json([
                'message' => 'Too many translations in a row. Please wait a moment.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        try {
            $translation = $this->translations->translate(
                $validated['text'],
                $from,
                $to,
                $validated['school_id'] ?? null,
            );
        } catch (TranslationFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['translation' => $translation]);
    }
}
