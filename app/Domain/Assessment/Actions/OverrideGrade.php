<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\AssessmentScore;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class OverrideGrade
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(AssessmentScore|ExamResult $score, float $newScore, ?string $reason = null): AssessmentScore|ExamResult
    {
        if ($score->school_id !== $this->school->id) {
            throw new Exception('Score record does not belong to this school.');
        }

        if ($newScore < 0) {
            throw new Exception('Score cannot be negative.');
        }

        $maxScore = $score instanceof ExamResult ? $score->exam->max_score : ($score->assessment->max_score ?? null);
        if ($maxScore !== null && $newScore > $maxScore) {
            throw new Exception("Score cannot exceed the maximum of {$maxScore}.");
        }

        return DB::transaction(function () use ($score, $newScore, $reason) {
            $score->update([
                'score' => $newScore,
                'feedback' => $reason
                    ? ($score->feedback ?? '')."\n[Grade overridden: {$reason}]"
                    : $score->feedback,
            ]);

            return $score->fresh();
        });
    }
}
