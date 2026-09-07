<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\FeeStructure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeStructurePolicy
{
    use HandlesAuthorization;

    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') ||
            $feeStructure->school_id === session('school_id');
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') &&
            $feeStructure->school_id === session('school_id');
    }

    public function delete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') &&
            $feeStructure->school_id === session('school_id');
    }
}
