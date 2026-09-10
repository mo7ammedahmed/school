<?php

declare(strict_types=1);

namespace App\Domain\People\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeacherAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $teacherId, public int $sectionId, public int $subjectId) {}
}
