<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Submission;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class SubmitAssignment
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Assignment $assignment, Student $student, ?string $content = null, ?string $filePath = null, ?string $fileType = null, ?int $fileSize = null): Submission
    {
        if ($assignment->school_id !== $this->school->id) {
            throw new Exception('Assignment does not belong to this school.');
        }

        if ($student->school_id !== $this->school->id) {
            throw new Exception('Student does not belong to this school.');
        }

        if (!$assignment->is_published) {
            throw new Exception('Assignment is not yet published.');
        }

        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingSubmission) {
            throw new Exception('Student has already submitted this assignment.');
        }

        $isLate = $assignment->due_date && now()->greaterThan($assignment->due_date);
        if ($isLate && !$assignment->allow_late_submission) {
            throw new Exception('Late submissions are not allowed for this assignment.');
        }

        if (empty($content) && empty($filePath)) {
            throw new Exception('Submission must include content or a file attachment.');
        }

        return DB::transaction(fn() => Submission::create([
            'school_id' => $this->school->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => $content,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'submitted_at' => now(),
        ]));
    }
}
