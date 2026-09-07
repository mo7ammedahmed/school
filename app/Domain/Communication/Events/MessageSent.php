<?php

declare(strict_types=1);

namespace App\Domain\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $messageId, public int $conversationId, public int $senderId)
    {
    }
}
