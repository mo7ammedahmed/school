<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('manage-enrollments') ||
            $enrollment->school_id === session('school_id');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('manage-enrollments') &&
            $enrollment->school_id === session('school_id');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermissionTo('manage-enrollments') &&
            $enrollment->school_id === session('school_id');
    }
}
