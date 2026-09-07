<?php

declare(strict_types=1);

namespace App\Domain\Communication\Policies;

use App\Domain\Communication\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage-announcements') ||
            $announcement->school_id === session('school_id');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage-announcements') &&
            $announcement->school_id === session('school_id');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('manage-announcements') &&
            $announcement->school_id === session('school_id');
    }
}
