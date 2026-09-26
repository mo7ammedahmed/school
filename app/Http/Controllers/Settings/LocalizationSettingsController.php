<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class LocalizationSettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'default_locale' => 'en',
        'default_timezone' => 'Asia/Riyadh',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'currency' => 'SAR',
        'currency_symbol' => 'SAR',
        'number_format' => '1,234.56',
        'week_start' => 0,
    ];

    public function index(): Response
    {
        return inertia('settings/localization', [
            'settings' => $this->settings('localization', self::DEFAULTS)->all(),
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_locale' => 'required|in:en,ar',
            'default_timezone' => 'required|string|max:64',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:10',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'number_format' => 'nullable|string|max:50',
            'week_start' => 'required|integer|min:0|max:6',
        ]);

        $this->settings('localization', self::DEFAULTS)->save($validated);

        return redirect()->route('settings.localization')->with('success', 'Localization settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
