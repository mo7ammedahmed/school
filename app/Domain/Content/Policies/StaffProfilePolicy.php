<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\StaffProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffProfilePolicy
{
    use HandlesAuthorization;

    public function view(User $user, StaffProfile $model): bool
    {
        return $user->hasPermissionTo('manage-staff-profiles') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, StaffProfile $model): bool
    {
        return $user->hasPermissionTo('manage-staff-profiles') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, StaffProfile $model): bool
    {
        return $user->hasPermissionTo('manage-staff-profiles') &&
            $model->school_id === session('school_id');
    }
}
