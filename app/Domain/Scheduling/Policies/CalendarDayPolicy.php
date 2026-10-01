<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\CalendarDay;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalendarDayPolicy
{
    use HandlesAuthorization;

    // checkPermissionTo() returns false for an unknown permission, where
    // hasPermissionTo() throws and would surface as a 500.
    public function view(User $user, CalendarDay $model): bool
    {
        return $user->checkPermissionTo('manage-calendar') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('manage-calendar');
    }

    public function update(User $user, CalendarDay $model): bool
    {
        return $user->checkPermissionTo('manage-calendar') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function delete(User $user, CalendarDay $model): bool
    {
        return $user->checkPermissionTo('manage-calendar') &&
            (int) $model->school_id === (int) session('school_id');
    }
}
