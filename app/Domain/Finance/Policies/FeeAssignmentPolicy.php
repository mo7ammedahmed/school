<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\FeeAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeAssignmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, FeeAssignment $model): bool
    {
        return $user->hasPermissionTo('manage-fee-assignments') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, FeeAssignment $model): bool
    {
        return $user->hasPermissionTo('manage-fee-assignments') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, FeeAssignment $model): bool
    {
        return $user->hasPermissionTo('manage-fee-assignments') &&
            $model->school_id === session('school_id');
    }
}
