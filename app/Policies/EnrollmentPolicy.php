<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Enrollment $model): bool
    {
        return $user->hasPermissionTo('manage-enrollments') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Enrollment $model): bool
    {
        return $user->hasPermissionTo('manage-enrollments') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Enrollment $model): bool
    {
        return $user->hasPermissionTo('manage-enrollments') &&
            $model->school_id === session('school_id');
    }
}
