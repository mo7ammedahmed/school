<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\People\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class ExamResultController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $results = ExamResult::where('school_id', $schoolId)
            ->with(['exam', 'student'])
            ->latest()
            ->paginate(15);

        return inertia('exam-results/index', ['results' => $results]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $exams = Exam::where('school_id', $schoolId)->orderBy('name')->get();
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('exam-results/create', ['exams' => $exams, 'students' => $students]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'score' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $result = ExamResult::create([
            'school_id' => session('school_id'),
            'exam_id' => $validated['exam_id'],
            'student_id' => $validated['student_id'],
            'score' => $validated['score'],
            'notes' => $validated['notes'] ?? null,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('exam-results.show', $result)->with('success', 'Exam result added successfully.');
    }

    public function show(ExamResult $result): Response
    {
        $this->authorizeSchool($result);
        $result->load(['exam', 'student']);

        return inertia('exam-results/show', ['result' => $result]);
    }

    public function edit(ExamResult $result): Response
    {
        $this->authorizeSchool($result);
        $schoolId = session('school_id');
        $exams = Exam::where('school_id', $schoolId)->orderBy('name')->get();
        $students = Student::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('exam-results/edit', [
            'result' => $result->load(['exam', 'student']),
            'exams' => $exams,
            'students' => $students,
        ]);
    }

    public function update(Request $request, ExamResult $result): RedirectResponse
    {
        $this->authorizeSchool($result);
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'score' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $result->update([
            'exam_id' => $validated['exam_id'],
            'student_id' => $validated['student_id'],
            'score' => $validated['score'],
            'notes' => $validated['notes'] ?? null,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('exam-results.show', $result)->with('success', 'Exam result updated successfully.');
    }

    public function destroy(ExamResult $result): RedirectResponse
    {
        $this->authorizeSchool($result);
        $result->delete();

        return redirect()->route('exam-results.index')->with('success', 'Exam result deleted successfully.');
    }

    private function authorizeSchool(ExamResult $result): void
    {
        if ((int) $result->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }
}
