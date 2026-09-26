<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesBilingualInput;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class GradeLevelController extends Controller
{
    use HandlesBilingualInput;

    public function index(): Response
    {
        $gradeLevels = GradeLevel::where('school_id', $this->schoolId())
            ->orderBy('level')
            ->paginate(15);

        return inertia('grade-levels/index', ['gradeLevels' => $gradeLevels]);
    }

    public function create(): Response
    {
        return inertia('grade-levels/create', []);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'level' => [
                'required', 'integer', 'min:1',
                Rule::unique('grade_levels', 'level')->where('school_id', $this->schoolId()),
            ],
            'description' => 'nullable|string',
        ]);

        // A grade level always belongs to the school the user is working in.
        $validated['school_id'] = $this->schoolId();

        $gradeLevel = GradeLevel::create($this->translateBilingual($validated));

        return redirect()->route('grade-levels.show', $gradeLevel)->with('success', 'Grade level created successfully.');
    }

    public function show(GradeLevel $gradeLevel): Response
    {
        $this->authorizeGradeLevel($gradeLevel);

        return inertia('grade-levels/show', ['gradeLevel' => $gradeLevel]);
    }

    public function edit(GradeLevel $gradeLevel): Response
    {
        $this->authorizeGradeLevel($gradeLevel);

        return inertia('grade-levels/edit', ['gradeLevel' => $gradeLevel]);
    }

    public function update(Request $request, GradeLevel $gradeLevel): RedirectResponse
    {
        $this->authorizeGradeLevel($gradeLevel);

        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'level' => [
                'required', 'integer', 'min:1',
                Rule::unique('grade_levels', 'level')
                    ->where('school_id', $this->schoolId())
                    ->ignore($gradeLevel->id),
            ],
            'description' => 'nullable|string',
        ]);

        $gradeLevel->update($this->translateBilingual($validated));

        return redirect()->route('grade-levels.show', $gradeLevel)->with('success', 'Grade level updated successfully.');
    }

    public function destroy(GradeLevel $gradeLevel): RedirectResponse
    {
        $this->authorizeGradeLevel($gradeLevel);

        $gradeLevel->delete();

        return redirect()->route('grade-levels.index')->with('success', 'Grade level deleted successfully.');
    }

    private function schoolId(): int
    {
        return (int) session('school_id');
    }

    private function authorizeGradeLevel(GradeLevel $gradeLevel): void
    {
        abort_unless((int) $gradeLevel->school_id === $this->schoolId(), 403);
    }
}
