<?php

declare(strict_types=1);

namespace App\Domain\Communication\Policies;

use App\Domain\Communication\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Notification $notification): bool
    {
        return $user->hasPermissionTo('manage-notifications') ||
            $notification->school_id === session('school_id');
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->hasPermissionTo('manage-notifications') &&
            $notification->school_id === session('school_id');
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->hasPermissionTo('manage-notifications') &&
            $notification->school_id === session('school_id');
    }
}
