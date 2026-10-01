<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\Offering;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\AssessmentScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(): Response
    {
        $schoolId = $this->schoolId();

        $assessments = Assessment::where('school_id', $schoolId)
            ->with(['offering.subject', 'offering.section', 'gradingCategory'])
            ->orderByDesc('due_date')
            ->paginate(15)
            ->through(fn (Assessment $assessment) => $this->toRow($assessment));

        return inertia('assessments/index', ['assessments' => $assessments]);
    }

    public function create(): Response
    {
        return inertia('assessments/create', [
            'offerings' => $this->offerings(),
            'gradingCategories' => $this->gradingCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId();

        $validated = $request->validate($this->rules($schoolId));

        $assessment = Assessment::create([
            ...$validated,
            'school_id' => $schoolId,
            'academic_year_id' => $this->academicYearIdFor((int) $validated['offering_id']),
        ]);

        return redirect()
            ->route('assessments.show', $assessment)
            ->with('success', 'Assessment created successfully.');
    }

    public function show(Assessment $assessment): Response
    {
        $assessment->load(['offering.subject', 'offering.section', 'gradingCategory']);

        return inertia('assessments/show', [
            'assessment' => [
                ...$this->toRow($assessment),
                'description' => $assessment->description,
            ],
        ]);
    }

    public function edit(Assessment $assessment): Response
    {
        return inertia('assessments/edit', [
            'assessment' => [
                ...$this->toRow($assessment),
                'description' => $assessment->description,
                'semester_id' => $assessment->semester_id,
                'grading_category_id' => $assessment->grading_category_id,
                'offering_id' => $assessment->offering_id,
            ],
            'offerings' => $this->offerings(),
            'gradingCategories' => $this->gradingCategories(),
        ]);
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate($this->rules($this->schoolId()));

        $assessment->update([
            ...$validated,
            'academic_year_id' => $this->academicYearIdFor((int) $validated['offering_id']),
        ]);

        return redirect()
            ->route('assessments.show', $assessment)
            ->with('success', 'Assessment updated successfully.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $assessment->delete();

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Assessment deleted successfully.');
    }

    /**
     * Score entry / review for a single assessment.
     */
    public function scores(Assessment $assessment): Response
    {
        $assessment->load('offering.subject', 'offering.section');

        $scores = AssessmentScore::where('assessment_id', $assessment->id)
            ->with(['student', 'gradedBy'])
            ->get()
            ->map(fn (AssessmentScore $score) => [
                'id' => $score->id,
                'student_name' => trim(($score->student?->first_name ?? '').' '.($score->student?->last_name ?? '')),
                'score' => $score->score === null ? null : (float) $score->score,
                'graded_by' => $score->gradedBy?->name,
                'graded_at' => $score->graded_at?->toDateTimeString(),
            ]);

        return inertia('assessments/scores', [
            'assessment' => [
                'id' => $assessment->id,
                'name' => $assessment->name,
                'max_score' => $assessment->max_score === null ? null : (float) $assessment->max_score,
                'subject' => $assessment->offering?->subject?->name,
                'section' => $assessment->offering?->section?->name,
            ],
            'scores' => $scores,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'name' => $assessment->name,
            'category' => $assessment->gradingCategory?->name,
            'subject' => $assessment->offering?->subject?->name,
            'section' => $assessment->offering?->section?->name,
            'due_date' => $assessment->due_date?->toDateString(),
            'max_score' => $assessment->max_score === null ? null : (float) $assessment->max_score,
            'weight' => $assessment->weight === null ? null : (float) $assessment->weight,
            'is_published' => (bool) $assessment->is_published,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(int $schoolId): array
    {
        return [
            'semester_id' => ['nullable', Rule::exists('semesters', 'id')->where('school_id', $schoolId)],
            'grading_category_id' => ['required', Rule::exists('grading_categories', 'id')->where('school_id', $schoolId)],
            'offering_id' => ['required', Rule::exists('offerings', 'id')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function offerings(): array
    {
        return Offering::where('school_id', $this->schoolId())
            ->with(['subject', 'section'])
            ->get()
            ->map(fn (Offering $offering) => [
                'id' => $offering->id,
                'label' => trim(($offering->subject?->name ?? '—').' · '.($offering->section?->name ?? '—')),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gradingCategories(): array
    {
        return GradingCategory::where('school_id', $this->schoolId())
            ->orderBy('name')
            ->get()
            ->map(fn (GradingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * The academic year always follows the chosen offering, so the form never
     * has to ask for it (and can never submit a mismatched pair).
     */
    private function academicYearIdFor(int $offeringId): int
    {
        return (int) Offering::where('school_id', $this->schoolId())
            ->where('id', $offeringId)
            ->value('academic_year_id');
    }
}
