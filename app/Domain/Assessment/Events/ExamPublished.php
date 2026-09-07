<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $examId, public int $publishedBy)
    {
    }
}
