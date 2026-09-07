<?php

declare(strict_types=1);

namespace App\Domain\Academics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SectionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $sectionId, public string $name, public int $gradeLevelId)
    {
    }
}
