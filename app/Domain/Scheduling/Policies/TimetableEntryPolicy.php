<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetableEntryPolicy
{
    use HandlesAuthorization;

    // checkPermissionTo() returns false for an unknown permission, where
    // hasPermissionTo() throws and would surface as a 500.
    public function view(User $user, TimetableEntry $model): bool
    {
        return $user->checkPermissionTo('manage-timetable-entries') ||
            (int) $model->school_id === (int) session('school_id');
    }

    public function update(User $user, TimetableEntry $model): bool
    {
        return $user->checkPermissionTo('manage-timetable-entries') &&
            (int) $model->school_id === (int) session('school_id');
    }

    public function delete(User $user, TimetableEntry $model): bool
    {
        return $user->checkPermissionTo('manage-timetable-entries') &&
            (int) $model->school_id === (int) session('school_id');
    }
}
