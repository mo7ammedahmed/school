<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GatewayTransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, GatewayTransaction $model): bool
    {
        return $user->hasPermissionTo('manage-gateway-transactions') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, GatewayTransaction $model): bool
    {
        return $user->hasPermissionTo('manage-gateway-transactions') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, GatewayTransaction $model): bool
    {
        return $user->hasPermissionTo('manage-gateway-transactions') &&
            $model->school_id === session('school_id');
    }
}
