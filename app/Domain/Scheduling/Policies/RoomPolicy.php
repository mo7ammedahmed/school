<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('manage-rooms') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('manage-rooms') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('manage-rooms') &&
            $model->school_id === session('school_id');
    }
}
