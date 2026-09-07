<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class GenerateReportCard
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Student $student, int $academicYearId, ?int $semesterId = null): ReportCard
    {
        if ($student->school_id !== $this->school->id) {
            throw new Exception('Student does not belong to this school.');
        }

        $query = ExamResult::whereHas('exam', function ($q) use ($academicYearId, $semesterId) {
            $q->where('academic_year_id', $academicYearId)
              ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId));
        })->where('student_id', $student->id);

        $results = $query->get();

        if ($results->isEmpty()) {
            throw new Exception('No exam results found for this student in the selected period.');
        }

        $grades = $results->map(fn ($result) => [
            'exam_id' => $result->exam_id,
            'exam_name' => $result->exam->name,
            'score' => $result->score,
            'max_score' => $result->exam->max_score,
            'graded_at' => $result->graded_at?->toDateString(),
        ])->toArray();

        $totalScore = $results->sum('score');
        $totalMax = $results->sum(fn ($r) => $r->exam->max_score);
        $gpa = $totalMax > 0 ? round(($totalScore / $totalMax) * 5, 2) : 0;

        return DB::transaction(fn() => ReportCard::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'gpa' => $gpa,
            'grades' => $grades,
            'published_at' => now(),
        ]));
    }
}
