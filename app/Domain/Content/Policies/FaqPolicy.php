<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\Faq;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FaqPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Faq $model): bool
    {
        return $user->hasPermissionTo('manage-faqs') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Faq $model): bool
    {
        return $user->hasPermissionTo('manage-faqs') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Faq $model): bool
    {
        return $user->hasPermissionTo('manage-faqs') &&
            $model->school_id === session('school_id');
    }
}
