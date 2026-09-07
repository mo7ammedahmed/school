<?php

declare(strict_types=1);

namespace App\Domain\Schools\Policies;

use App\Domain\Schools\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Organization $model): bool
    {
        return $user->hasPermissionTo('manage-organizations') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Organization $model): bool
    {
        return $user->hasPermissionTo('manage-organizations') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Organization $model): bool
    {
        return $user->hasPermissionTo('manage-organizations') &&
            $model->school_id === session('school_id');
    }
}
