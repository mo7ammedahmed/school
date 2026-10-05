<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Learning\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $quizzes = Quiz::where('school_id', $schoolId)
            ->with(['offering.subject', 'offering.section'])
            ->latest()
            ->paginate(15);

        return inertia('quizzes/index', ['quizzes' => $quizzes]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('quizzes/create', ['subjects' => $subjects, 'sections' => $sections]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'nullable|in:draft,published,closed',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $quiz = Quiz::create([
            'school_id' => $schoolId,
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'questions' => [],
            'time_limit_minutes' => $validated['duration_minutes'] ?? 30,
            'max_score' => $validated['total_marks'],
            'is_published' => ($validated['status'] ?? 'draft') === 'published',
        ]);

        return redirect()->route('quizzes.show', $quiz)->with('success', 'Quiz created successfully.');
    }

    public function show(Quiz $quiz): Response
    {
        $quiz->load(['offering.subject', 'offering.section']);

        return inertia('quizzes/show', ['quiz' => $quiz]);
    }

    public function edit(Quiz $quiz): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('quizzes/edit', [
            'quiz' => $quiz->load(['offering.subject', 'offering.section']),
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'nullable|in:draft,published,closed',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $quiz->update([
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['duration_minutes'] ?? $quiz->time_limit_minutes,
            'max_score' => $validated['total_marks'],
            'is_published' => ($validated['status'] ?? 'draft') === 'published',
        ]);

        return redirect()->route('quizzes.show', $quiz)->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()->route('quizzes.index')->with('success', 'Quiz deleted successfully.');
    }

    public function attempt(Quiz $quiz): Response
    {
        if (auth()->user()->can('manage-quizzes')) {
            $this->authorize('view', $quiz);

            return $this->show($quiz);
        }

        $this->authorize('attempt', $quiz);

        $quiz->load(['offering.subject', 'offering.section']);

        $payload = $quiz->only(['id', 'title', 'total_marks', 'passing_marks', 'duration_minutes', 'status']);
        $payload['subject'] = $quiz->subject?->only(['name']);
        $payload['section'] = $quiz->section?->only(['name']);
        $payload['questions'] = collect($quiz->questions ?? [])
            ->map(fn (array $question): array => collect($question)
                ->only(['number', 'type', 'prompt', 'options', 'points'])->all() + ['answer' => null])
            ->all();

        return inertia('quizzes/show', ['quiz' => $payload, 'canManage' => false]);
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
