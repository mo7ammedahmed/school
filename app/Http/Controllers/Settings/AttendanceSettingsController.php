<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AttendanceSettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULTS = [
        'late_threshold_minutes' => 15,
        'excused_types' => ['sick', 'excused'],
    ];

    public function index(): Response
    {
        $settings = $this->settings('attendance', self::DEFAULTS)->all();

        // The form edits the list as a comma separated string; normalise here so
        // the page never has to cope with a missing or scalar value.
        $settings['excused_types'] = is_array($settings['excused_types'] ?? null)
            ? array_values($settings['excused_types'])
            : [];

        return inertia('settings/attendance', [
            'settings' => $settings,
        ]);
    }

    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'late_threshold_minutes' => 'required|integer|min:0|max:240',
            'excused_types' => 'nullable|string|max:255',
        ]);

        $types = array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) ($validated['excused_types'] ?? '')),
        )));

        $this->settings('attendance', self::DEFAULTS)->save([
            'late_threshold_minutes' => $validated['late_threshold_minutes'],
            'excused_types' => $types,
        ]);

        return redirect()->route('settings.attendance')->with('success', 'Attendance settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
