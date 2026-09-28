<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Localization\Enums\TranslationProvider;
use App\Domain\Localization\Exceptions\TranslationFailed;
use App\Domain\Localization\Services\AiTranslator;
use App\Domain\Localization\Services\BilingualBackfill;
use App\Domain\Localization\Services\TranslationSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * Settings -> Translations. Controls whether the system fills in the Arabic (or
 * English) side of bilingual content automatically, which AI service does the
 * work, and with which key.
 */
class TranslationSettingsController extends Controller
{
    public function __construct(
        private readonly AiTranslator $translator,
        private readonly BilingualBackfill $backfill,
    ) {}

    public function index(): Response
    {
        $settings = TranslationSettings::for($this->schoolId());

        return inertia('settings/translations', [
            'settings' => $settings->masked(),
            'providers' => TranslationProvider::options(),
            'locales' => [
                ['value' => 'en', 'label' => 'English'],
                ['value' => 'ar', 'label' => 'Arabic'],
            ],
            'endpoint' => $settings->endpoint(),
            'envKeyConfigured' => $settings->keySource() === 'environment',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_column(TranslationProvider::options(), 'value'))],
            'model' => 'required|string|max:255',
            'base_url' => ['nullable', 'url', 'max:255', 'required_if:provider,custom'],
            'auto_translate' => 'required|boolean',
            'source_locale' => 'required|in:en,ar',
            'target_locale' => 'required|in:en,ar|different:source_locale',
            'api_key' => 'nullable|string|max:1000',
            'clear_api_key' => 'nullable|boolean',
        ]);

        TranslationSettings::for($this->schoolId())->save($validated);

        return back()->with('success', 'Translation settings saved.');
    }

    /**
     * Deliberately tries a real translation so an operator finds out now rather
     * than when a save silently skips Arabic.
     */
    public function test(Request $request): JsonResponse|RedirectResponse
    {
        $settings = TranslationSettings::for($this->schoolId());

        $result = $this->translator->test($settings);

        $message = $result['ok']
            ? 'Connection works — "Hello" translated to "'.$result['detail'].'".'
            : $result['detail'];

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $result['ok'],
                'message' => $message,
                'detail' => $result['detail'],
            ], $result['ok'] ? 200 : 422);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $message);
    }

    /**
     * Sweeps every bilingual table for this school and translates whichever
     * language is empty, so content that predates the AI provider can be brought
     * fully bilingual in one action.
     */
    public function backfill(Request $request): JsonResponse
    {
        // The sweep is one provider call per value, so the screen walks it in
        // batches and shows progress instead of holding one request open for
        // minutes. `limit = 0` scans without translating, which is how the
        // screen sizes the backlog before it starts.
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:0|max:50',
        ]);

        $schoolId = $this->schoolId();
        $settings = TranslationSettings::for($schoolId);

        // Fail before touching anything if there is no key to translate with.
        if (! $settings->isConfigured()) {
            return response()->json([
                'ok' => false,
                'done' => true,
                'message' => 'Add an API key for '.$settings->provider()->label().' before translating. Nothing was changed.',
            ], 422);
        }

        $limit = array_key_exists('limit', $validated) ? (int) $validated['limit'] : null;

        $result = $this->backfill->run($schoolId, $limit);
        $totals = $result['totals'];
        $ok = $result['error'] === null;

        return response()->json([
            'ok' => $ok,
            'done' => $ok && $totals['remaining'] === 0,
            'message' => $this->backfillMessage($totals),
            'error' => $result['error'],
            'totals' => $totals,
            'targets' => array_values(array_filter(
                $result['targets'],
                static fn (array $target): bool => $target['scanned'] > 0,
            )),
        ], $ok ? 200 : 422);
    }

    /**
     * @param  array{scanned: int, missing: int, translated: int, failed: int, remaining: int, en_to_ar: int, ar_to_en: int}  $totals
     */
    private function backfillMessage(array $totals): string
    {
        if ($totals['missing'] === 0) {
            return 'Nothing was missing — every record already has both Arabic and English.';
        }

        if ($totals['translated'] === 0) {
            return 'Found '.$totals['missing'].' missing values but none could be translated.';
        }

        $message = 'Translated '.$totals['translated'].' of '.$totals['missing'].' missing values';

        // Say which way they went: a school that types in Arabic needs the
        // English side filled, and the reverse, so report both.
        $directions = [];
        if ($totals['en_to_ar'] > 0) {
            $directions[] = $totals['en_to_ar'].' into Arabic';
        }
        if ($totals['ar_to_en'] > 0) {
            $directions[] = $totals['ar_to_en'].' into English';
        }

        if ($directions !== []) {
            $message .= ' ('.implode(', ', $directions).')';
        }

        if ($totals['failed'] > 0) {
            $message .= ', '.$totals['failed'].' failed';
        }

        if ($totals['remaining'] > 0) {
            $message .= '. '.$totals['remaining'].' left — run it again to continue.';
        }

        return $message.'.';
    }

    /**
     * The provider's live model list, so a retired model id can be replaced
     * from the screen rather than needing a code change.
     */
    public function models(): JsonResponse
    {
        try {
            $models = $this->translator->listModels(TranslationSettings::for($this->schoolId()));
        } catch (TranslationFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['models' => $models]);
    }

    private function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        return $schoolId;
    }
}
