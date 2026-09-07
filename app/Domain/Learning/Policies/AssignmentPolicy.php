<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo('manage-assignments') ||
            $assignment->school_id === session('school_id');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo('manage-assignments') &&
            $assignment->school_id === session('school_id');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->hasPermissionTo('manage-assignments') &&
            $assignment->school_id === session('school_id');
    }
}
