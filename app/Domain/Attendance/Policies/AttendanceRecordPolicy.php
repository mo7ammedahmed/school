<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Policies;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->hasPermissionTo('manage-attendance-records') ||
            $attendanceRecord->school_id === session('school_id');
    }

    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->hasPermissionTo('manage-attendance-records') &&
            $attendanceRecord->school_id === session('school_id');
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->hasPermissionTo('manage-attendance-records') &&
            $attendanceRecord->school_id === session('school_id');
    }
}
