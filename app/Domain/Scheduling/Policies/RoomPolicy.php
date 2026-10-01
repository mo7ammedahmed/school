<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    // checkPermissionTo() returns false for an unknown permission, where
    // hasPermissionTo() throws and would surface as a 500.
    public function view(User $user, Room $model): bool
    {
        return $user->checkPermissionTo('manage-rooms') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function update(User $user, Room $model): bool
    {
        return $user->checkPermissionTo('manage-rooms') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function delete(User $user, Room $model): bool
    {
        return $user->checkPermissionTo('manage-rooms') &&
            (int) $model->school_id === (int) session('school_id');
    }
}
