<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetableEntryPolicy
{
    use HandlesAuthorization;

    public function view(User $user, TimetableEntry $model): bool
    {
        return $user->hasPermissionTo('manage-timetable-entries') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, TimetableEntry $model): bool
    {
        return $user->hasPermissionTo('manage-timetable-entries') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, TimetableEntry $model): bool
    {
        return $user->hasPermissionTo('manage-timetable-entries') &&
            $model->school_id === session('school_id');
    }
}
