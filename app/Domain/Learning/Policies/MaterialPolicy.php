<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Material;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Material $material): bool
    {
        return $user->hasPermissionTo('manage-materials') ||
            $material->school_id === session('school_id');
    }

    public function update(User $user, Material $material): bool
    {
        return $user->hasPermissionTo('manage-materials') &&
            $material->school_id === session('school_id');
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->hasPermissionTo('manage-materials') &&
            $material->school_id === session('school_id');
    }
}
