<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\Semester;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SemesterPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Semester $semester): bool
    {
        return $user->hasPermissionTo('manage-semesters') ||
            $semester->school_id === session('school_id');
    }

    public function update(User $user, Semester $semester): bool
    {
        return $user->hasPermissionTo('manage-semesters') &&
            $semester->school_id === session('school_id');
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $user->hasPermissionTo('manage-semesters') &&
            $semester->school_id === session('school_id');
    }
}
