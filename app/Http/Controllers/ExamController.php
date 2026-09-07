<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $exams = Exam::where('school_id', $schoolId)
            ->with(['offering.subject', 'offering.section'])
            ->latest()
            ->paginate(15);

        return inertia('exams/index', ['exams' => $exams]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name')->get();

        return inertia('exams/create', ['subjects' => $subjects, 'sections' => $sections]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'total_marks' => 'required|numeric|min:1',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled,draft,published',
        ]);

        $offering = $this->resolveOffering($validated['offering_id'] ?? null, $validated['subject_id'] ?? null, $validated['section_id'] ?? null);

        $exam = Exam::create([
            'school_id' => $schoolId,
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $this->currentSemesterId($offering->academic_year_id),
            'offering_id' => $offering->id,
            'name' => $validated['name'],
            'description' => null,
            'exam_date' => $validated['exam_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_id' => null,
            'max_score' => $validated['total_marks'],
            'is_published' => ! in_array($validated['status'] ?? 'scheduled', ['draft', 'cancelled']),
        ]);

        return redirect()->route('exams.show', $exam)->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): Response
    {
        $this->authorizeSchool($exam);
        $exam->load(['offering.subject', 'offering.section', 'room']);

        return inertia('exams/show', ['exam' => $exam]);
    }

    public function edit(Exam $exam): Response
    {
        $this->authorizeSchool($exam);
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name')->get();

        return inertia('exams/edit', [
            'exam' => $exam->load(['offering.subject', 'offering.section']),
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeSchool($exam);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'total_marks' => 'required|numeric|min:1',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled,draft,published',
        ]);

        $offering = $this->resolveOffering($validated['offering_id'] ?? null, $validated['subject_id'] ?? null, $validated['section_id'] ?? null);

        $exam->update([
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $this->currentSemesterId($offering->academic_year_id),
            'offering_id' => $offering->id,
            'name' => $validated['name'],
            'exam_date' => $validated['exam_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'max_score' => $validated['total_marks'],
            'is_published' => ! in_array($validated['status'] ?? 'scheduled', ['draft', 'cancelled']),
        ]);

        return redirect()->route('exams.show', $exam)->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeSchool($exam);
        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Exam deleted successfully.');
    }

    public function results(Exam $exam): Response
    {
        $this->authorizeSchool($exam);
        $results = ExamResult::where('school_id', session('school_id'))
            ->where('exam_id', $exam->id)
            ->with(['student'])
            ->latest()
            ->paginate(15);

        return inertia('exam-results/index', ['results' => $results, 'exam' => $exam]);
    }

    private function currentSemesterId(int $academicYearId): int
    {
        return (int) Semester::where('school_id', session('school_id'))
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->value('id');
    }

    private function authorizeSchool(Exam $exam): void
    {
        if ((int) $exam->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }

    private function resolveOffering(?int $offeringId, ?int $subjectId, ?int $sectionId): Offering
    {
        $query = Offering::where('school_id', session('school_id'));

        if ($offeringId) {
            $offering = $query->find($offeringId);
        } else {
            $offering = (clone $query)
                ->where('subject_id', $subjectId)
                ->where('section_id', $sectionId)
                ->first();
        }

        if (! $offering) {
            throw ValidationException::withMessages([
                'subject_id' => 'No active offering exists for this subject and section.',
            ]);
        }

        return $offering;
    }
}
