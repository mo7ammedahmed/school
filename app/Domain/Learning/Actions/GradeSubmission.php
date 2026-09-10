<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\Submission;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class GradeSubmission
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Submission $submission, float $score, ?string $feedback = null, ?User $grader = null): Submission
    {
        if ($submission->school_id !== $this->school->id) {
            throw new Exception('Submission does not belong to this school.');
        }

        $assignment = $submission->assignment;

        if ($score < 0) {
            throw new Exception('Score cannot be negative.');
        }

        if ($score > $assignment->max_score) {
            throw new Exception("Score ({$score}) cannot exceed the assignment maximum ({$assignment->max_score}).");
        }

        return DB::transaction(function () use ($submission, $score, $feedback, $grader) {
            $submission->update([
                'score' => $score,
                'feedback' => $feedback,
                'graded_by' => $grader?->id,
                'graded_at' => now(),
            ]);

            return $submission->fresh();
        });
    }
}
