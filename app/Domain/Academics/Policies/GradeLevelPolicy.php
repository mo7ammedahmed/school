<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\GradeLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeLevelPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GradeLevel $gradeLevel): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') ||
            $gradeLevel->school_id === session('school_id');
    }

    public function update(User $user, GradeLevel $gradeLevel): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') &&
            $gradeLevel->school_id === session('school_id');
    }

    public function delete(User $user, GradeLevel $gradeLevel): bool
    {
        return $user->hasPermissionTo('manage-grade-levels') &&
            $gradeLevel->school_id === session('school_id');
    }
}
