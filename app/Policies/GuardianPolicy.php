<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\People\Models\Guardian;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorizes a guardian.
 *
 * The parameter is the domain `Guardian`, not the `App\Models\Guardian`
 * subclass, because `Gate` looks a policy up under the exact class a
 * controller binds and the two names resolve to this class either way. Hinting
 * the subclass would leave a `TypeError` waiting for the first caller that
 * binds the domain model directly.
 */
class GuardianPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, Guardian $model): bool
    {
        return $user->hasPermissionTo('manage-guardians') &&
            $model->school_id === session('school_id');
    }
}
