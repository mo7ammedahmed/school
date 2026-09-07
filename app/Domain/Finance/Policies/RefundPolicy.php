<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Refund $refund): bool
    {
        return $user->hasPermissionTo('manage-refunds') ||
            $refund->school_id === session('school_id');
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->hasPermissionTo('manage-refunds') &&
            $refund->school_id === session('school_id');
    }

    public function delete(User $user, Refund $refund): bool
    {
        return $user->hasPermissionTo('manage-refunds') &&
            $refund->school_id === session('school_id');
    }
}
