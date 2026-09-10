<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $userId, public string $email) {}
}
