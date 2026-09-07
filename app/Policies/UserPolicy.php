<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users') ||
            $model->currentMembership?->school_id === session('school_id');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users') &&
            $model->currentMembership?->school_id === session('school_id');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users') &&
            $model->currentMembership?->school_id === session('school_id');
    }
}
