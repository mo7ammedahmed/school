<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Event $model): bool
    {
        return $user->hasPermissionTo('manage-events') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Event $model): bool
    {
        return $user->hasPermissionTo('manage-events') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Event $model): bool
    {
        return $user->hasPermissionTo('manage-events') &&
            $model->school_id === session('school_id');
    }
}
