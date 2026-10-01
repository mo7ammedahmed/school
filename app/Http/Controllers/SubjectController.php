<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::where('school_id', $this->schoolId())
            ->select('id', 'code', 'grade_level_id', 'name_ar', 'name_en')
            ->with('gradeLevel')
            ->latest()
            ->paginate(15);

        return inertia('subjects/index', ['subjects' => $subjects]);
    }

    public function create(): Response
    {
        return inertia('subjects/create', ['gradeLevels' => $this->gradeLevels()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('subjects', 'code')->where('school_id', $this->schoolId()),
            ],
            'grade_level_id' => ['required', 'integer', $this->gradeLevelRule()],
            'description' => 'nullable|string',
        ]);

        // The table requires a tenant, and `grade_level_id` is a real column —
        // both used to be dropped on the floor here.
        $validated['school_id'] = $this->schoolId();

        $subject = Subject::create($validated);

        return redirect()->route('subjects.show', $subject)->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject): Response
    {
        $subject->load('gradeLevel');

        return inertia('subjects/show', ['subject' => $subject]);
    }

    public function edit(Subject $subject): Response
    {
        return inertia('subjects/edit', [
            'subject' => $subject,
            'gradeLevels' => $this->gradeLevels(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('subjects', 'code')
                    ->where('school_id', $this->schoolId())
                    ->ignore($subject->id),
            ],
            'grade_level_id' => ['required', 'integer', $this->gradeLevelRule()],
            'description' => 'nullable|string',
        ]);

        $subject->update($validated);

        return redirect()->route('subjects.show', $subject)->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }

    /**
     * @return Collection<int, GradeLevel>
     */
    private function gradeLevels(): Collection
    {
        return GradeLevel::where('school_id', $this->schoolId())->orderBy('level')->get();
    }

    private function gradeLevelRule(): Exists
    {
        return Rule::exists('grade_levels', 'id')->where('school_id', $this->schoolId());
    }
}
