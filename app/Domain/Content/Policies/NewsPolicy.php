<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\News;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function view(User $user, News $model): bool
    {
        return $user->hasPermissionTo('manage-news') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, News $model): bool
    {
        return $user->hasPermissionTo('manage-news') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, News $model): bool
    {
        return $user->hasPermissionTo('manage-news') &&
            $model->school_id === session('school_id');
    }
}
