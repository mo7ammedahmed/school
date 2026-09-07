<?php

declare(strict_types=1);

namespace App\Domain\Schools\Policies;

use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolPolicy
{
    use HandlesAuthorization;

    public function view(User $user, School $model): bool
    {
        return $user->hasPermissionTo('manage-schools') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, School $model): bool
    {
        return $user->hasPermissionTo('manage-schools') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, School $model): bool
    {
        return $user->hasPermissionTo('manage-schools') &&
            $model->school_id === session('school_id');
    }
}
