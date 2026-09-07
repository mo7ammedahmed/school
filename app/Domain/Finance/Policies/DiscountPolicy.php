<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Discount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('manage-discounts') ||
            $discount->school_id === session('school_id');
    }

    public function update(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('manage-discounts') &&
            $discount->school_id === session('school_id');
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('manage-discounts') &&
            $discount->school_id === session('school_id');
    }
}
