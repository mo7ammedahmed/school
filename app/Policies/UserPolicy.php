<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users') &&
            $model->memberships()->where('school_id', session('school_id'))->exists();
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users')
            && $this->view($user, $model) && $this->canManageAccount($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage-users') && $this->update($user, $model);
    }

    private function canManageAccount(User $user, User $model): bool
    {
        // Roles, credentials and deletion affect the entire account. School
        // operators must not change platform operators or accounts shared
        // with another school.
        return ! $model->hasRole('super_admin')
            && ! $model->memberships()->where('school_id', '!=', session('school_id'))->exists()
            && $model->getAllPermissions()->pluck('name')->diff($user->getAllPermissions()->pluck('name'))->isEmpty();
    }
}
