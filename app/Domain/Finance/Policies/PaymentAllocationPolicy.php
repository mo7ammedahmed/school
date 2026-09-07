<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentAllocationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PaymentAllocation $model): bool
    {
        return $user->hasPermissionTo('manage-payment-allocations') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, PaymentAllocation $model): bool
    {
        return $user->hasPermissionTo('manage-payment-allocations') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, PaymentAllocation $model): bool
    {
        return $user->hasPermissionTo('manage-payment-allocations') &&
            $model->school_id === session('school_id');
    }
}
