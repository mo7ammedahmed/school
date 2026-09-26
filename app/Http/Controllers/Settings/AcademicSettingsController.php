<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\InteractsWithSchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AcademicSettingsController extends Controller
{
    use InteractsWithSchoolSettings;

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULTS = [
        'grading_system' => 'percentage',
        'pass_mark' => 50,
        'max_score' => 100,
    ];

    public function index(): Response
    {
        return inertia('settings/academic', [
            'settings' => $this->settings('academic', self::DEFAULTS)->all(),
        ]);
    }

    /** Kept for callers that still address the screen as an "edit" action. */
    public function edit(): Response
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grading_system' => 'required|in:percentage,letter,gpa',
            'pass_mark' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:1|max:1000',
        ]);

        $this->settings('academic', self::DEFAULTS)->save($validated);

        return redirect()->route('settings.academic')->with('success', 'Academic settings updated successfully.');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->store($request);
    }
}
