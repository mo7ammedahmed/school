<?php

declare(strict_types=1);

namespace App\Domain\People\Policies;

use App\Domain\People\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    public function view(User $user, TeacherProfile $teacher): bool
    {
        return $user->hasPermissionTo('manage-teachers') ||
            $teacher->school_id === session('school_id');
    }

    public function update(User $user, TeacherProfile $teacher): bool
    {
        return $user->hasPermissionTo('manage-teachers') &&
            $teacher->school_id === session('school_id');
    }

    public function delete(User $user, TeacherProfile $teacher): bool
    {
        return $user->hasPermissionTo('manage-teachers') &&
            $teacher->school_id === session('school_id');
    }
}
