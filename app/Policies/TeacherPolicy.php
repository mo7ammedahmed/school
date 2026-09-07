<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Teacher $model): bool
    {
        return $user->hasPermissionTo('manage-teachers') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Teacher $model): bool
    {
        return $user->hasPermissionTo('manage-teachers') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Teacher $model): bool
    {
        return $user->hasPermissionTo('manage-teachers') &&
            $model->school_id === session('school_id');
    }
}
