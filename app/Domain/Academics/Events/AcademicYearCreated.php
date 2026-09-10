<?php

declare(strict_types=1);

namespace App\Domain\Academics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademicYearCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $academicYearId, public string $name) {}
}
