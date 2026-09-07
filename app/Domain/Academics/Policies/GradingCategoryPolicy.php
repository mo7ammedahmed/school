<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\GradingCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradingCategoryPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GradingCategory $gradingCategory): bool
    {
        return $user->hasPermissionTo('manage-grading-categories') ||
            $gradingCategory->school_id === session('school_id');
    }

    public function update(User $user, GradingCategory $gradingCategory): bool
    {
        return $user->hasPermissionTo('manage-grading-categories') &&
            $gradingCategory->school_id === session('school_id');
    }

    public function delete(User $user, GradingCategory $gradingCategory): bool
    {
        return $user->hasPermissionTo('manage-grading-categories') &&
            $gradingCategory->school_id === session('school_id');
    }
}
