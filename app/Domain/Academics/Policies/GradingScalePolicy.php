<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\GradingScale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradingScalePolicy
{
    use HandlesAuthorization;

    public function view(User $user, GradingScale $gradingScale): bool
    {
        return $user->hasPermissionTo('manage-grading-scales') ||
            $gradingScale->school_id === session('school_id');
    }

    public function update(User $user, GradingScale $gradingScale): bool
    {
        return $user->hasPermissionTo('manage-grading-scales') &&
            $gradingScale->school_id === session('school_id');
    }

    public function delete(User $user, GradingScale $gradingScale): bool
    {
        return $user->hasPermissionTo('manage-grading-scales') &&
            $gradingScale->school_id === session('school_id');
    }
}
