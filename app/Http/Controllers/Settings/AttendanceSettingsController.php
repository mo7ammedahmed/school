<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class AttendanceSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = config('settings.attendance', [
            'allow_late_arrival' => true,
            'late_mark_after_minutes' => 15,
            'half_day_threshold_minutes' => 120,
            'auto_absent_after_minutes' => 240,
            'require_guardian_justification' => true,
            'allow_self_justification' => true,
            'track_break_in_out' => false,
        ]);

        return inertia('settings/attendance/edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'allow_late_arrival' => 'required|boolean',
            'late_mark_after_minutes' => 'required|integer|min:1',
            'half_day_threshold_minutes' => 'required|integer|min:1',
            'auto_absent_after_minutes' => 'required|integer|min:1',
            'require_guardian_justification' => 'required|boolean',
            'allow_self_justification' => 'required|boolean',
            'track_break_in_out' => 'required|boolean',
        ]);

        $settingsPath = config_path('settings.php');

        if (!file_exists($settingsPath)) {
            file_put_contents($settingsPath, "<?php\n\nreturn [\n    'attendance' => [],\n];\n");
        }

        $settings = require $settingsPath;
        $settings['attendance'] = $validated;

        $export = var_export($settings, true);
        $content = "<?php\n\nreturn " . $export . ";\n";

        file_put_contents($settingsPath, $content);

        return redirect()->route('settings.attendance.edit')->with('success', 'Attendance settings updated successfully.');
    }
}
