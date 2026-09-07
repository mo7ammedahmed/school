<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasPermissionTo('manage-subjects') ||
            $subject->school_id === session('school_id');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasPermissionTo('manage-subjects') &&
            $subject->school_id === session('school_id');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasPermissionTo('manage-subjects') &&
            $subject->school_id === session('school_id');
    }
}
