<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Submission;
use App\Domain\People\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function index(): Response
    {
        $submissions = Submission::where('school_id', session('school_id'))
            ->with(['assignment', 'student'])
            ->latest()
            ->paginate(15);

        return inertia('submissions/index', ['submissions' => $submissions]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $assignments = Assignment::where('school_id', $schoolId)->orderBy('title')->get();
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('submissions/create', ['assignments' => $assignments, 'students' => $students]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:students,id',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions', 'public');
            $validated['file_path'] = $path;
            $validated['file_type'] = $request->file('file')->extension() ?: $request->file('file')->getClientOriginalExtension();
            $validated['file_size'] = $request->file('file')->getSize();
        }

        $submission = Submission::create([
            'school_id' => session('school_id'),
            'assignment_id' => $validated['assignment_id'],
            'student_id' => $validated['student_id'],
            'content' => $validated['content'] ?? null,
            'file_path' => $validated['file_path'] ?? null,
            'file_type' => $validated['file_type'] ?? null,
            'file_size' => $validated['file_size'] ?? null,
            'submitted_at' => now(),
        ]);

        return redirect()->route('submissions.show', $submission)->with('success', 'Submission created successfully.');
    }

    public function show(Submission $submission): Response
    {
        $this->authorizeSchool($submission);
        $submission->load(['assignment', 'student']);

        return inertia('submissions/show', ['submission' => $submission]);
    }

    public function edit(Submission $submission): Response
    {
        $this->authorizeSchool($submission);
        $schoolId = session('school_id');
        $assignments = Assignment::where('school_id', $schoolId)->orderBy('title')->get();
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('submissions/edit', [
            'submission' => $submission->load(['assignment', 'student']),
            'assignments' => $assignments,
            'students' => $students,
        ]);
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeSchool($submission);
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:students,id',
            'content' => 'nullable|string',
            'score' => 'nullable|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'assignment_id' => $validated['assignment_id'],
            'student_id' => $validated['student_id'],
            'content' => $validated['content'] ?? null,
            'score' => $validated['score'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'graded_by' => array_key_exists('score', $validated) && $validated['score'] !== null ? auth()->id() : $submission->graded_by,
            'graded_at' => array_key_exists('score', $validated) && $validated['score'] !== null ? now() : $submission->graded_at,
        ]);

        return redirect()->route('submissions.show', $submission)->with('success', 'Submission updated successfully.');
    }

    public function destroy(Submission $submission): RedirectResponse
    {
        $this->authorizeSchool($submission);
        if ($submission->file_path && Storage::disk('public')->exists($submission->file_path)) {
            Storage::disk('public')->delete($submission->file_path);
        }

        $submission->delete();

        return redirect()->route('submissions.index')->with('success', 'Submission deleted successfully.');
    }

    private function authorizeSchool(Submission $submission): void
    {
        if ((int) $submission->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }
}
