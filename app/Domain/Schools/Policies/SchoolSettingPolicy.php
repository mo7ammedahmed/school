<?php

declare(strict_types=1);

namespace App\Domain\Schools\Policies;

use App\Domain\Schools\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolSettingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SchoolSetting $model): bool
    {
        return $user->hasPermissionTo('manage-school-settings') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, SchoolSetting $model): bool
    {
        return $user->hasPermissionTo('manage-school-settings') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, SchoolSetting $model): bool
    {
        return $user->hasPermissionTo('manage-school-settings') &&
            $model->school_id === session('school_id');
    }
}
