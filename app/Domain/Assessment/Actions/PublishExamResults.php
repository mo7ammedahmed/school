<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Exam;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class PublishExamResults
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Exam $exam): Exam
    {
        if ($exam->school_id !== $this->school->id) {
            throw new Exception('Exam does not belong to this school.');
        }

        if ($exam->is_published) {
            throw new Exception('Exam results have already been published.');
        }

        $ungradedCount = $exam->results()->whereNull('score')->count();
        if ($ungradedCount > 0) {
            throw new Exception("Cannot publish results. {$ungradedCount} result(s) are still ungraded.");
        }

        return DB::transaction(function () use ($exam) {
            $exam->update(['is_published' => true]);
            return $exam->fresh();
        });
    }
}
