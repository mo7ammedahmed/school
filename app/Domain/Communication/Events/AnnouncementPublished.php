<?php

declare(strict_types=1);

namespace App\Domain\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $announcementId, public string $audience) {}
}
