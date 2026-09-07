<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\WebhookEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookEventPolicy
{
    use HandlesAuthorization;

    public function view(User $user, WebhookEvent $model): bool
    {
        return $user->hasPermissionTo('manage-webhook-events') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, WebhookEvent $model): bool
    {
        return $user->hasPermissionTo('manage-webhook-events') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, WebhookEvent $model): bool
    {
        return $user->hasPermissionTo('manage-webhook-events') &&
            $model->school_id === session('school_id');
    }
}
