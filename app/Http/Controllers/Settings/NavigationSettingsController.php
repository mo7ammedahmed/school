<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolNavigationLabel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NavigationSettingsController extends Controller
{
    public function index(): Response
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        $labels = SchoolNavigationLabel::where('school_id', $school->id)
            ->get(['key', 'name_en', 'name_ar']);

        return Inertia::render('settings/navigation', [
            'labels' => $labels->mapWithKeys(fn (SchoolNavigationLabel $label): array => [
                $label->key => [
                    'en' => $label->name_en,
                    'ar' => $label->name_ar,
                ],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->activeSchool();
        abort_unless($school, 404);

        $validated = $request->validate([
            'labels' => ['required', 'array'],
            'labels.*.en' => ['nullable', 'string', 'max:255'],
            'labels.*.ar' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['labels'] as $key => $values) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            SchoolNavigationLabel::updateOrCreate(
                ['school_id' => $school->id, 'key' => $key],
                [
                    'name_en' => $values['en'] ?? null,
                    'name_ar' => $values['ar'] ?? null,
                ]
            );
        }

        return redirect()->route('settings.navigation')->with('success', 'Navigation labels updated successfully.');
    }

    private function activeSchool(): ?School
    {
        $schoolId = session('school_id');

        if (! $schoolId) {
            return null;
        }

        $user = auth()->user();

        if (! $user->hasRole('super_admin') && ! $user->memberships()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->exists()) {
            return null;
        }

        return School::find($schoolId);
    }
}
