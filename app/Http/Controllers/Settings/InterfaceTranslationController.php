<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Localization\Models\InterfaceTranslation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InterfaceTranslationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'english' => ['required', 'string', 'max:300'],
            'arabic' => ['required', 'string', 'max:5000'],
        ]);

        $english = trim($validated['english']);
        InterfaceTranslation::query()->updateOrCreate(
            ['source_hash' => InterfaceTranslation::hashSource($english)],
            [
                'english' => $english,
                'arabic' => trim($validated['arabic']),
                'updated_by' => $request->user()->id,
            ],
        );

        return back()->with('success', 'Shared interface translation saved.');
    }

    public function destroy(Request $request, InterfaceTranslation $translation): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $translation->delete();

        return back()->with('success', 'Shared interface translation removed.');
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
    }
}
