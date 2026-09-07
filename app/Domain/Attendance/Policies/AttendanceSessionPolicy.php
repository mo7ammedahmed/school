<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Policies;

use App\Domain\Attendance\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceSessionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasPermissionTo('manage-attendance-sessions') ||
            $attendanceSession->school_id === session('school_id');
    }

    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasPermissionTo('manage-attendance-sessions') &&
            $attendanceSession->school_id === session('school_id');
    }

    public function delete(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->hasPermissionTo('manage-attendance-sessions') &&
            $attendanceSession->school_id === session('school_id');
    }
}
