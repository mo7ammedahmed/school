<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->hasPermissionTo('manage-assessments') ||
            $assessment->school_id === session('school_id');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->hasPermissionTo('manage-assessments') &&
            $assessment->school_id === session('school_id');
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->hasPermissionTo('manage-assessments') &&
            $assessment->school_id === session('school_id');
    }
}
