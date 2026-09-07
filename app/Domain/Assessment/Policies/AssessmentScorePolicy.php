<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\AssessmentScore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentScorePolicy
{
    use HandlesAuthorization;

    public function view(User $user, AssessmentScore $assessmentScore): bool
    {
        return $user->hasPermissionTo('manage-assessment-scores') ||
            $assessmentScore->school_id === session('school_id');
    }

    public function update(User $user, AssessmentScore $assessmentScore): bool
    {
        return $user->hasPermissionTo('manage-assessment-scores') &&
            $assessmentScore->school_id === session('school_id');
    }

    public function delete(User $user, AssessmentScore $assessmentScore): bool
    {
        return $user->hasPermissionTo('manage-assessment-scores') &&
            $assessmentScore->school_id === session('school_id');
    }
}
