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
        return $user->hasPermissionTo('manage-content-pages') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, ContentPage $model): bool
    {
        return $user->hasPermissionTo('manage-content-pages') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, ContentPage $model): bool
    {
        return $user->hasPermissionTo('manage-content-pages') &&
            $model->school_id === session('school_id');
    }
}
