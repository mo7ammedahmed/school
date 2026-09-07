<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Models\UserMembership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserMembershipPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserMembership $model): bool
    {
        return $user->hasPermissionTo('manage-memberships') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, UserMembership $model): bool
    {
        return $user->hasPermissionTo('manage-memberships') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, UserMembership $model): bool
    {
        return $user->hasPermissionTo('manage-memberships') &&
            $model->school_id === session('school_id');
    }
}
