<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Subject $model): bool
    {
        return $user->hasPermissionTo('manage-subjects') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Subject $model): bool
    {
        return $user->hasPermissionTo('manage-subjects') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Subject $model): bool
    {
        return $user->hasPermissionTo('manage-subjects') &&
            $model->school_id === session('school_id');
    }
}
