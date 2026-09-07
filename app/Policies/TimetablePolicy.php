<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetablePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Timetable $model): bool
    {
        return $user->hasPermissionTo('manage-timetable') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Timetable $model): bool
    {
        return $user->hasPermissionTo('manage-timetable') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Timetable $model): bool
    {
        return $user->hasPermissionTo('manage-timetable') &&
            $model->school_id === session('school_id');
    }
}
