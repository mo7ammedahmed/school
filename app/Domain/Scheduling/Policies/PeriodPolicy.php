<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\Period;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PeriodPolicy
{
    use HandlesAuthorization;

    // checkPermissionTo() returns false for an unknown permission, where
    // hasPermissionTo() throws and would surface as a 500.
    public function view(User $user, Period $model): bool
    {
        return $user->checkPermissionTo('manage-periods') ||
            (int) $model->school_id === (int) session('school_id');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('manage-periods');
    }

    public function update(User $user, Period $model): bool
    {
        return $user->checkPermissionTo('manage-periods') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function delete(User $user, Period $model): bool
    {
        return $user->checkPermissionTo('manage-periods') &&
            (int) $model->school_id === (int) session('school_id');
    }
}
