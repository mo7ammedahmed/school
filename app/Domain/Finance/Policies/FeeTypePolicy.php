<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\FeeType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeeTypePolicy
{
    use HandlesAuthorization;

    public function view(User $user, FeeType $model): bool
    {
        return $user->hasPermissionTo('manage-fee-types') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, FeeType $model): bool
    {
        return $user->hasPermissionTo('manage-fee-types') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, FeeType $model): bool
    {
        return $user->hasPermissionTo('manage-fee-types') &&
            $model->school_id === session('school_id');
    }
}
