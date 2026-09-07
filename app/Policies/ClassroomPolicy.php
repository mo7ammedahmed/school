<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassroomPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Classroom $model): bool
    {
        return $user->hasPermissionTo('manage-classrooms') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Classroom $model): bool
    {
        return $user->hasPermissionTo('manage-classrooms') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Classroom $model): bool
    {
        return $user->hasPermissionTo('manage-classrooms') &&
            $model->school_id === session('school_id');
    }
}
