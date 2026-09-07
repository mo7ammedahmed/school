<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\GradingScale;
use App\Domain\Schools\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GradingSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        // Get the current school from the request (set by school.context middleware)
        $school = $request->attributes->get('school');

        if (!$school) {
            // Fallback for when school context is not available
            $school = $request->user()->currentSchool ?? $request->user()->schools()->first();
        }

        // Fetch grading scales for the school
        $gradingScales = GradingScale::where('school_id', $school->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn($scale) => [
                'id' => $scale->id,
                'name' => $scale->name,
                'description' => $scale->description,
                'scale' => json_decode($scale->scale, true),
                'is_default' => $scale->is_default,
            ]);

        // Fetch grading categories for the school
        $gradingCategories = GradingCategory::where('school_id', $school->id)
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'weight' => floatval($category->weight),
                'description' => $category->description,
            ]);

        // Fetch grading settings from school_settings table
        $roundingMethodSetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', 'grading_rounding_method')
            ->first();
        $roundingMethod = $roundingMethodSetting ? $roundingMethodSetting->value : 'nearest';

        $includeExtracurricularSetting = SchoolSetting::where('school_id', $school->id)
            ->where('key', 'grading_include_extracurricular')
            ->first();
        $includeExtracurricular = $includeExtracurricularSetting
            ? filter_var($includeExtracurricularSetting->value, FILTER_VALIDATE_BOOLEAN)
            : true;

        return inertia('settings/grading/edit', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
            ],
            'gradingScales' => $gradingScales,
            'gradingCategories' => $gradingCategories,
            'settings' => [
                'rounding_method' => $roundingMethod,
                'include_extracurricular' => $includeExtracurricular,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Get the current school from the request (set by school.context middleware)
        $school = $request->attributes->get('school');

        if (!$school) {
            // Fallback for when school context is not available
            $school = $request->user()->currentSchool ?? $request->user()->schools()->first();
        }

        $request->validate([
            // Grading scales data
            'gradingScales' => 'sometimes|array',
            'gradingScales.*.id' => 'sometimes|integer|exists:grading_scales,id',
            'gradingScales.*.name' => 'sometimes|required|string|max:100',
            'gradingScales.*.description' => 'sometimes|nullable|string',
            'gradingScales.*.scale' => 'sometimes|required|array',
            'gradingScales.*.scale.*.grade' => 'sometimes|required|string|max:10',
            'gradingScales.*.scale.*.min' => 'sometimes|required|numeric|min:0|max:100',
            'gradingScales.*.scale.*.max' => 'sometimes|required|numeric|min:0|max:100',
            'gradingScales.*.is_default' => 'sometimes|boolean',

            // New grading scales
            'newGradingScales' => 'sometimes|array',
            'newGradingScales.*.name' => 'sometimes|required|string|max:100',
            'newGradingScales.*.description' => 'sometimes|nullable|string',
            'newGradingScales.*.scale' => 'sometimes|required|array',
            'newGradingScales.*.scale.*.grade' => 'sometimes|required|string|max:10',
            'newGradingScales.*.scale.*.min' => 'sometimes|required|numeric|min:0|max:100',
            'newGradingScales.*.scale.*.max' => 'sometimes|required|numeric|min:0|max:100',

            // Grading categories data
            'gradingCategories' => 'sometimes|array',
            'gradingCategories.*.id' => 'sometimes|integer|exists:grading_categories,id',
            'gradingCategories.*.name' => 'sometimes|required|string|max:100',
            'gradingCategories.*.code' => 'sometimes|nullable|string|max:20',
            'gradingCategories.*.weight' => 'sometimes|required|numeric|min:0.01|max:100',
            'gradingCategories.*.description' => 'sometimes|nullable|string',

            // New grading categories
            'newGradingCategories' => 'sometimes|array',
            'newGradingCategories.*.name' => 'sometimes|required|string|max:100',
            'newGradingCategories.*.code' => 'sometimes|nullable|string|max:20',
            'newGradingCategories.*.weight' => 'sometimes|required|numeric|min:0.01|max:100',
            'newGradingCategories.*.description' => 'sometimes|nullable|string',

            // Settings to delete
            'deleteGradingScaleIds' => 'sometimes|array',
            'deleteGradingScaleIds.*' => 'sometimes|integer|exists:grading_scales,id',
            'deleteGradingCategoryIds' => 'sometimes|array',
            'deleteGradingCategoryIds.*' => 'sometimes|integer|exists:grading_categories,id',

            // Basic settings
            'rounding_method' => 'required|in:nearest,floor,ceil',
            'include_extracurricular' => 'required|boolean',
        ]);

        // Handle deleting grading scales
        if ($request->has('deleteGradingScaleIds')) {
            GradingScale::whereIn('id', $request->input('deleteGradingScaleIds'))
                ->where('school_id', $school->id)
                ->delete();
        }

        // Handle deleting grading categories
        if ($request->has('deleteGradingCategoryIds')) {
            GradingCategory::whereIn('id', $request->input('deleteGradingCategoryIds'))
                ->where('school_id', $school->id)
                ->delete();
        }

        // Handle updating existing grading scales
        if ($request->has('gradingScales')) {
            foreach ($request->input('gradingScales') as $scaleData) {
                if (isset($scaleData['id'])) {
                    // Update existing scale
                    $scale = GradingScale::where('id', $scaleData['id'])
                        ->where('school_id', $school->id)
                        ->first();

                    if ($scale) {
                        $scale->update([
                            'name' => $scaleData['name'],
                            'description' => $scaleData['description'] ?? null,
                            'scale' => json_encode($scaleData['scale']),
                            'is_default' => $scaleData['is_default'] ?? false,
                        ]);

                        // If this scale is set as default, unset default for all other scales
                        if ($scaleData['is_default'] ?? false) {
                            GradingScale::where('school_id', $school->id)
                                ->where('id', '!=', $scale->id)
                                ->update(['is_default' => false]);
                        }
                    }
                }
            }
        }

        // Handle creating new grading scales
        if ($request->has('newGradingScales')) {
            foreach ($request->input('newGradingScales') as $scaleData) {
                $scale = GradingScale::create([
                    'school_id' => $school->id,
                    'name' => $scaleData['name'],
                    'description' => $scaleData['description'] ?? null,
                    'scale' => json_encode($scaleData['scale']),
                    'is_default' => $scaleData['is_default'] ?? false,
                ]);

                // If this scale is set as default, unset default for all other scales
                if ($scaleData['is_default'] ?? false) {
                    GradingScale::where('school_id', $school->id)
                        ->where('id', '!=', $scale->id)
                        ->update(['is_default' => false]);
                }
            }
        }

        // Handle updating existing grading categories
        if ($request->has('gradingCategories')) {
            foreach ($request->input('gradingCategories') as $categoryData) {
                if (isset($categoryData['id'])) {
                    // Update existing category
                    $category = GradingCategory::where('id', $categoryData['id'])
                        ->where('school_id', $school->id)
                        ->first();

                    if ($category) {
                        $category->update([
                            'name' => $categoryData['name'],
                            'code' => $categoryData['code'] ?? null,
                            'weight' => $categoryData['weight'],
                            'description' => $categoryData['description'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Handle creating new grading categories
        if ($request->has('newGradingCategories')) {
            foreach ($request->input('newGradingCategories') as $categoryData) {
                GradingCategory::create([
                    'school_id' => $school->id,
                    'name' => $categoryData['name'],
                    'code' => $categoryData['code'] ?? null,
                    'weight' => $categoryData['weight'],
                    'description' => $categoryData['description'] ?? null,
                ]);
            }
        }

        // Update school settings
        SchoolSetting::updateOrCreate(
            ['school_id' => $school->id, 'key' => 'grading_rounding_method'],
            ['value' => $request->input('rounding_method'), 'type' => 'string']
        );

        SchoolSetting::updateOrCreate(
            ['school_id' => $school->id, 'key' => 'grading_include_extracurricular'],
            ['value' => $request->input('include_extracurricular') ? 'true' : 'false', 'type' => 'boolean']
        );

        return redirect()->route('settings.grading.edit')->with('success', 'Grading settings updated successfully.');
    }

    // Individual grading scale management methods
    public function storeScale(Request $request): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'scale' => 'required|array',
            'scale.*.grade' => 'required|string|max:10',
            'scale.*.min' => 'required|numeric|min:0|max:100',
            'scale.*.max' => 'required|numeric|min:0|max:100',
            'is_default' => 'sometimes|boolean',
        ]);

        $scale = GradingScale::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'scale' => json_encode($validated['scale']),
            'is_default' => $validated['is_default'] ?? false,
        ]);

        // If this scale is set as default, unset default for all other scales
        if ($validated['is_default'] ?? false) {
            GradingScale::where('school_id', $school->id)
                ->where('id', '!=', $scale->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('settings.grading.edit')->with('success', 'Grading scale created successfully.');
    }

    public function showScale(GradingScale $scale): Response
    {
        // Ensure the scale belongs to the current school
        $school = request()->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        if ($scale->school_id !== $school->id) {
            abort(403);
        }

        return inertia('settings/grading/edit', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
            ],
            'gradingScales' => [$scale]->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'scale' => json_decode($s->scale, true),
                'is_default' => $s->is_default,
            ]),
            'gradingCategories' => GradingCategory::where('school_id', $school->id)
                ->orderBy('name')
                ->get()
                ->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'weight' => floatval($category->weight),
                    'description' => $category->description,
                ]),
            'settings' => [
                'rounding_method' => SchoolSetting::where('school_id', $school->id)
                    ->where('key', 'grading_rounding_method')
                    ->first()?->value ?? 'nearest',
                'include_extracurricular' => filter_var(
                    SchoolSetting::where('school_id', $school->id)
                        ->where('key', 'grading_include_extracurricular')
                        ->first()?->value ?? 'true',
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            // Pass the scale data to pre-fill the edit form
            'editingScaleId' => $scale->id,
            'scaleData' => [
                'name' => $scale->name,
                'description' => $scale->description,
                'scale' => json_decode($scale->scale, true),
                'isDefaultScale' => $scale->is_default,
            ],
        ]);
    }

    public function updateScale(Request $request, GradingScale $scale): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        // Ensure the scale belongs to the current school
        if ($scale->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string',
            'scale' => 'sometimes|required|array',
            'scale.*.grade' => 'sometimes|required|string|max:10',
            'scale.*.min' => 'sometimes|required|numeric|min:0|max:100',
            'scale.*.max' => 'sometimes|required|numeric|min:0|max:100',
            'is_default' => 'sometimes|boolean',
        ]);

        $scale->update([
            'name' => $validated['name'] ?? $scale->name,
            'description' => $validated['description'] ?? $scale->description,
            'scale' => $validated['scale'] ? json_encode($validated['scale']) : $scale->scale,
            'is_default' => $validated['is_default'] ?? $scale->is_default,
        ]);

        // If this scale is set as default, unset default for all other scales
        if ($validated['is_default'] ?? false) {
            GradingScale::where('school_id', $school->id)
                ->where('id', '!=', $scale->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('settings.grading.edit')->with('success', 'Grading scale updated successfully.');
    }

    public function destroyScale(GradingScale $scale): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        // Ensure the scale belongs to the current school
        if ($scale->school_id !== $school->id) {
            abort(403);
        }

        // If deleting the default scale, we need to set another one as default
        $wasDefault = $scale->is_default;

        $scale->delete();

        // If we deleted the default scale, set another scale as default if any exist
        if ($wasDefault) {
            $anotherScale = GradingScale::where('school_id', $school->id)->first();
            if ($anotherScale) {
                $anotherScale->update(['is_default' => true]);
            }
        }

        return redirect()->route('settings.grading.edit')->with('success', 'Grading scale deleted successfully.');
    }

    // Individual grading category management methods
    public function storeCategory(Request $request): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'weight' => 'required|numeric|min:0.01|max:100',
            'description' => 'nullable|string',
        ]);

        GradingCategory::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'weight' => $validated['weight'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('settings.grading.edit')->with('success', 'Grading category created successfully.');
    }

    public function showCategory(GradingCategory $category): Response
    {
        // Ensure the category belongs to the current school
        $school = request()->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        if ($category->school_id !== $school->id) {
            abort(403);
        }

        return inertia('settings/grading/edit', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
            ],
            'gradingScales' => GradingScale::where('school_id', $school->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get()
                ->map(fn($scale) => [
                    'id' => $scale->id,
                    'name' => $scale->name,
                    'description' => $scale->description,
                    'scale' => json_decode($scale->scale, true),
                    'is_default' => $scale->is_default,
                ]),
            'gradingCategories' => [$category]->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'weight' => floatval($c->weight),
                'description' => $c->description,
            ]),
            'settings' => [
                'rounding_method' => SchoolSetting::where('school_id', $school->id)
                    ->where('key', 'grading_rounding_method')
                    ->first()?->value ?? 'nearest',
                'include_extracurricular' => filter_var(
                    SchoolSetting::where('school_id', $school->id)
                        ->where('key', 'grading_include_extracurricular')
                        ->first()?->value ?? 'true',
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            // Pass the category data to pre-fill the edit form
            'editingCategoryId' => $category->id,
            'categoryData' => [
                'name' => $category->name,
                'code' => $category->code,
                'weight' => $category->weight,
                'description' => $category->description,
            ],
        ]);
    }

    public function updateCategory(Request $request, GradingCategory $category): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        // Ensure the category belongs to the current school
        if ($category->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|nullable|string|max:20',
            'weight' => 'sometimes|required|numeric|min:0.01|max:100',
            'description' => 'sometimes|nullable|string',
        ]);

        $category->update([
            'name' => $validated['name'] ?? $category->name,
            'code' => $validated['code'] ?? $category->code,
            'weight' => $validated['weight'] ?? $category->weight,
            'description' => $validated['description'] ?? $category->description,
        ]);

        return redirect()->route('settings.grading.edit')->with('success', 'Grading category updated successfully.');
    }

    public function destroyCategory(GradingCategory $category): RedirectResponse
    {
        $school = $request->attributes->get('school') ?? ($request->user()->currentSchool ?? $request->user()->schools()->first());

        // Ensure the category belongs to the current school
        if ($category->school_id !== $school->id) {
            abort(403);
        }

        $category->delete();

        return redirect()->route('settings.grading.edit')->with('success', 'Grading category deleted successfully.');
    }
}
