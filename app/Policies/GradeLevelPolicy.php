<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GradeLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeLevelPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GradeLevel $model): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, GradeLevel $model): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, GradeLevel $model): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') &&
            $model->school_id === session('school_id');
    }
}
