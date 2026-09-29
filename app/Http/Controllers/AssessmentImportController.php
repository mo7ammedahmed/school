<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Semester;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Services\DocxQuestionParser;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Scheduling\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import a Word question paper and turn it into an exam or a quiz.
 *
 * Flow: choose a target + upload (.parse) → review the parsed questions on the
 * same screen → confirm (.store) which writes the record.
 */
class AssessmentImportController extends Controller
{
    public function __construct(private readonly DocxQuestionParser $parser) {}

    public function create(): Response
    {
        return Inertia::render('assessments/import', [
            'offerings' => $this->offerings(),
            'preview' => session('import_preview'),
            'defaults' => [
                'type' => 'exam',
                'exam_date' => Carbon::today()->toDateString(),
                'max_score' => null,
                'time_limit_minutes' => null,
            ],
        ]);
    }

    /**
     * Read the uploaded document and bounce back with the parsed preview.
     */
    public function parse(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId();

        $validated = $request->validate([
            'type' => ['required', 'in:exam,quiz'],
            'offering_id' => ['required', Rule::exists('offerings', 'id')->where('school_id', $schoolId)],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['required', 'file', 'max:8192', 'extensions:docx'],
            'exam_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $file = $request->file('document');
        $fallbackTitle = $validated['name']
            ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        try {
            $parsed = $this->parser->parse($this->parser->extractText($file->getRealPath()), $fallbackTitle);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()]);
        }

        return back()->with('import_preview', [
            'type' => $validated['type'],
            'offering_id' => (int) $validated['offering_id'],
            'name' => $parsed['title'] ?: $fallbackTitle,
            'description' => $parsed['description'] ?? ($validated['description'] ?? null),
            'exam_date' => $validated['exam_date'] ?? Carbon::today()->toDateString(),
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'room' => $validated['room'] ?? null,
            'max_score' => $validated['max_score'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'questions' => $parsed['questions'],
            'warnings' => $parsed['warnings'],
            'source' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Persist the reviewed question paper.
     */
    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId();

        $validated = $request->validate([
            'type' => ['required', 'in:exam,quiz'],
            'offering_id' => ['required', Rule::exists('offerings', 'id')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,short_answer'],
            'questions.*.answer' => ['nullable'],
            'questions.*.points' => ['nullable', 'numeric', 'min:0'],
            'questions.*.options' => ['nullable', 'array'],
            'exam_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:50'],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $offering = Offering::where('school_id', $schoolId)->findOrFail($validated['offering_id']);

        $questions = array_values($validated['questions']);
        $maxScore = $validated['max_score'] ?? $this->sumPoints($questions);

        if ($validated['type'] === 'exam') {
            $exam = Exam::create([
                'school_id' => $schoolId,
                'academic_year_id' => $offering->academic_year_id,
                'semester_id' => $this->semesterFor((int) $offering->academic_year_id),
                'offering_id' => $offering->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'exam_date' => $validated['exam_date'] ?? Carbon::today()->toDateString(),
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'room_id' => $this->roomId($validated['room'] ?? null),
                'max_score' => $maxScore,
                'questions' => $questions,
                'is_published' => false,
            ]);

            return redirect()
                ->route('exams.show', $exam)
                ->with('success', "Imported {$exam->name} with ".count($questions).' questions.');
        }

        $quiz = Quiz::create([
            'school_id' => $schoolId,
            'offering_id' => $offering->id,
            'title' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'questions' => $questions,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'max_score' => $maxScore,
            'is_published' => false,
        ]);

        return redirect()
            ->route('quizzes.show', $quiz)
            ->with('success', "Imported {$quiz->title} with ".count($questions).' questions.');
    }

    /**
     * Downloadable .docx skeleton so the format is unambiguous.
     */
    public function template(): BinaryFileResponse
    {
        $path = $this->parser->buildTemplate(storage_path('app/tmp'));

        return response()
            ->download($path, 'question-paper-template.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function sumPoints(array $questions): float
    {
        return array_reduce(
            $questions,
            fn (float $carry, array $question) => $carry + (float) ($question['points'] ?? 1),
            0.0,
        );
    }

    private function semesterFor(int $academicYearId): ?int
    {
        $semesterId = Semester::where('school_id', $this->schoolId())
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->value('id');

        return $semesterId === null ? null : (int) $semesterId;
    }

    private function roomId(?string $roomName): ?int
    {
        if (! $roomName) {
            return null;
        }

        return Room::where('school_id', $this->schoolId())
            ->where(fn ($query) => $query->where('name_en', $roomName)->orWhere('name_ar', $roomName))
            ->value('id');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function offerings(): array
    {
        return Offering::where('school_id', $this->schoolId())
            ->with(['subject:id,name_en,name_ar', 'section:id,name_en,name_ar'])
            ->get()
            ->map(fn (Offering $offering) => [
                'id' => $offering->id,
                'name' => trim(($offering->subject?->name ?? 'Subject').' · '.($offering->section?->name ?? 'Section')),
            ])
            ->values()
            ->all();
    }
}
