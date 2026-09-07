<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('manage-payments') ||
            $payment->school_id === session('school_id');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('manage-payments') &&
            $payment->school_id === session('school_id');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('manage-payments') &&
            $payment->school_id === session('school_id');
    }
}
