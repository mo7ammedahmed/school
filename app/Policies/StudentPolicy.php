<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Student $model): bool
    {
        return $user->hasPermissionTo('manage-students') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Student $model): bool
    {
        return $user->hasPermissionTo('manage-students') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Student $model): bool
    {
        return $user->hasPermissionTo('manage-students') &&
            $model->school_id === session('school_id') &&
            $model->status !== 'graduated';
    }
}
