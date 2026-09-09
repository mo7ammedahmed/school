<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\ContentPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContentPagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, ContentPage $model): bool
    {
        return $model->school_id === session('school_id')
            && ($user->hasPermissionTo('manage-content') || $user->hasPermissionTo('manage-content-pages'));
    }

    public function update(User $user, ContentPage $model): bool
    {
        return $model->school_id === session('school_id')
            && ($user->hasPermissionTo('manage-content') || $user->hasPermissionTo('manage-content-pages'));
    }

    public function delete(User $user, ContentPage $model): bool
    {
        return $model->school_id === session('school_id')
            && ($user->hasPermissionTo('manage-content') || $user->hasPermissionTo('manage-content-pages'));
    }
}
