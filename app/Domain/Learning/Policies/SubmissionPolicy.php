<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubmissionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo('manage-submissions') ||
            $submission->school_id === session('school_id');
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo('manage-submissions') &&
            $submission->school_id === session('school_id');
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo('manage-submissions') &&
            $submission->school_id === session('school_id');
    }
}
