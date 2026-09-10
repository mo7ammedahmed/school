<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Learning\Models\Assignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $assignments = Assignment::where('school_id', $schoolId)
            ->with(['offering.subject', 'offering.section'])
            ->latest()
            ->paginate(15);

        return inertia('assignments/index', ['assignments' => $assignments]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('assignments/create', ['subjects' => $subjects, 'sections' => $sections]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'due_date' => 'required|date',
            'total_marks' => 'required|numeric|min:1',
            'status' => 'nullable|in:draft,published,closed',
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $assignment = Assignment::create([
            'school_id' => $schoolId,
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => null,
            'due_date' => $validated['due_date'],
            'max_score' => $validated['total_marks'],
            'allow_late_submission' => true,
            'is_published' => ($validated['status'] ?? 'draft') === 'published',
        ]);

        return redirect()->route('assignments.show', $assignment)->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment): Response
    {
        $this->authorizeSchool($assignment);
        $assignment->load(['offering.subject', 'offering.section']);

        return inertia('assignments/show', ['assignment' => $assignment]);
    }

    public function edit(Assignment $assignment): Response
    {
        $this->authorizeSchool($assignment);
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('assignments/edit', [
            'assignment' => $assignment->load(['offering.subject', 'offering.section']),
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeSchool($assignment);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'due_date' => 'required|date',
            'total_marks' => 'required|numeric|min:1',
            'status' => 'nullable|in:draft,published,closed',
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $assignment->update([
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'max_score' => $validated['total_marks'],
            'is_published' => ($validated['status'] ?? 'draft') === 'published',
        ]);

        return redirect()->route('assignments.show', $assignment)->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorizeSchool($assignment);
        $assignment->delete();

        return redirect()->route('assignments.index')->with('success', 'Assignment deleted successfully.');
    }

    public function myAssignments(): Response
    {
        return $this->index();
    }

    private function authorizeSchool(Assignment $assignment): void
    {
        if ((int) $assignment->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }

    private function resolveOfferingId(int $subjectId, int $sectionId): int
    {
        $offering = Offering::where('school_id', session('school_id'))
            ->where('subject_id', $subjectId)
            ->where('section_id', $sectionId)
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'subject_id' => 'No active offering exists for this subject and section.',
            ]);
        }

        return (int) $offering->id;
    }
}
