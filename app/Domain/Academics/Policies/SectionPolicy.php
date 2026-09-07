<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Section $section): bool
    {
        return $user->hasPermissionTo('manage-sections') ||
            $section->school_id === session('school_id');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->hasPermissionTo('manage-sections') &&
            $section->school_id === session('school_id');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->hasPermissionTo('manage-sections') &&
            $section->school_id === session('school_id');
    }
}
