<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GuardianPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') &&
            $model->school_id === session('school_id');
    }
}
