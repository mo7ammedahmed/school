<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Section $model): bool
    {
        return $user->hasPermissionTo('manage-sections') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Section $model): bool
    {
        return $user->hasPermissionTo('manage-sections') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Section $model): bool
    {
        return $user->hasPermissionTo('manage-sections') &&
            $model->school_id === session('school_id');
    }
}
