<?php

declare(strict_types=1);

namespace App\Domain\Communication\Policies;

use App\Domain\Communication\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') ||
            $conversation->school_id === session('school_id');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') &&
            $conversation->school_id === session('school_id');
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') &&
            $conversation->school_id === session('school_id');
    }
}
