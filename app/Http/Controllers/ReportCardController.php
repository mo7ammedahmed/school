<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * A report card is a student's GPA, its per-subject grades and a comment in both
 * languages, published (or not) with a timestamp.
 *
 * The screen asked for a `grade`, a `status` on draft/published/archived and a
 * free-text `remarks` — none of which the table has. The model dropped all three,
 * so a card was created with a student, a year and a GPA and the screens that
 * read those fields rendered blanks. The three real fields are `comments` and
 * `comments_ar` (the registered bilingual pair) and `published_at`.
 */
class ReportCardController extends Controller
{
    public function index(): Response
    {
        $reportCards = ReportCard::where('school_id', $this->schoolId())
            ->with(['student', 'academicYear'])
            ->latest()
            ->paginate(15);

        return inertia('report-cards/index', ['reportCards' => $reportCards]);
    }

    public function create(): Response
    {
        return inertia('report-cards/create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $reportCard = ReportCard::create($validated + [
            'school_id' => $this->schoolId(),
            'published_by' => $validated['published_at'] === null ? null : $request->user()?->id,
        ]);

        return redirect()->route('report-cards.show', $reportCard)->with('success', 'Report card created successfully.');
    }

    public function show(ReportCard $reportCard): Response
    {
        $reportCard->load('student', 'academicYear');

        return inertia('report-cards/show', ['reportCard' => $reportCard]);
    }

    public function edit(ReportCard $reportCard): Response
    {
        return inertia('report-cards/edit', $this->formOptions() + ['reportCard' => $reportCard]);
    }

    public function update(Request $request, ReportCard $reportCard): RedirectResponse
    {
        $validated = $this->validated($request);

        $reportCard->update($validated + [
            'published_by' => $validated['published_at'] === null ? null : $request->user()?->id,
        ]);

        return redirect()->route('report-cards.show', $reportCard)->with('success', 'Report card updated successfully.');
    }

    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        $reportCard->delete();

        return redirect()->route('report-cards.index')->with('success', 'Report card deleted successfully.');
    }

    /**
     * Either language satisfies the comment pair; the empty side is translated
     * on save. Students and years are this school's only, so neither a stale
     * dropdown nor a crafted request can attach another school's record.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $schoolId = $this->schoolId();

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'semester_id' => ['nullable', Rule::exists('semesters', 'id')->where('school_id', $schoolId)],
            'gpa' => ['required', 'numeric', 'min:0', 'max:4'],
            'comments' => ['nullable', 'string', 'max:2000', 'required_without:comments_ar'],
            'comments_ar' => ['nullable', 'string', 'max:2000', 'required_without:comments'],
            'is_published' => ['required', 'boolean'],
        ]);

        // The table keeps a publication *timestamp*, not a status word, so the
        // toggle is what decides whether the card is visible to a guardian.
        $validated['published_at'] = (bool) $validated['is_published'] ? now() : null;
        unset($validated['is_published']);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'students' => Student::where('school_id', $this->schoolId())->orderBy('first_name')->get(),
            'academicYears' => AcademicYear::where('school_id', $this->schoolId())->orderBy('name_en', 'desc')->get(),
        ];
    }
}
