<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\Offering;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Offering $offering): bool
    {
        return $user->hasPermissionTo('manage-offerings') ||
            $offering->school_id === session('school_id');
    }

    public function update(User $user, Offering $offering): bool
    {
        return $user->hasPermissionTo('manage-offerings') &&
            $offering->school_id === session('school_id');
    }

    public function delete(User $user, Offering $offering): bool
    {
        return $user->hasPermissionTo('manage-offerings') &&
            $offering->school_id === session('school_id');
    }
}
