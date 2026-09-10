<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AcademicSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = config('settings.academic', [
            'grading_system' => 'percentage',
            'pass_marks_percentage' => 40,
            'attendance_required_percentage' => 75,
            'max_working_days' => 220,
            'late_arrival_tolerance_minutes' => 15,
            'enable_auto_grade_calculation' => true,
        ]);

        return inertia('settings/academic/edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grading_system' => 'required|in:percentage,letter,gpa',
            'pass_marks_percentage' => 'required|integer|min:0|max:100',
            'attendance_required_percentage' => 'required|integer|min:0|max:100',
            'max_working_days' => 'required|integer|min:1|max:366',
            'late_arrival_tolerance_minutes' => 'required|integer|min:0|max:120',
            'enable_auto_grade_calculation' => 'required|boolean',
        ]);

        $settingsPath = config_path('settings.php');

        if (! file_exists($settingsPath)) {
            file_put_contents($settingsPath, "<?php\n\nreturn [\n    'academic' => [],\n];\n");
        }

        $settings = require $settingsPath;
        $settings['academic'] = $validated;

        $export = var_export($settings, true);
        $content = "<?php\n\nreturn ".$export.";\n";

        file_put_contents($settingsPath, $content);

        return redirect()->route('settings.academic.edit')->with('success', 'Academic settings updated successfully.');
    }
}
