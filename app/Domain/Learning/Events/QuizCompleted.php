<?php

declare(strict_types=1);

namespace App\Domain\Learning\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $attemptId, public int $quizId, public int $studentId)
    {
    }
}
