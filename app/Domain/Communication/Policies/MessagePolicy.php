<?php

declare(strict_types=1);

namespace App\Domain\Communication\Policies;

use App\Domain\Communication\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Message $message): bool
    {
        return $user->hasPermissionTo('manage-messages') ||
            $message->school_id === session('school_id');
    }

    public function update(User $user, Message $message): bool
    {
        return $user->hasPermissionTo('manage-messages') &&
            $message->school_id === session('school_id');
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->hasPermissionTo('manage-messages') &&
            $message->school_id === session('school_id');
    }
}
