<?php

declare(strict_types=1);

namespace App\Domain\Academics\Policies;

use App\Domain\Academics\Models\AcademicYear;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicYearPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage-academic-years') ||
            $academicYear->school_id === session('school_id');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage-academic-years') &&
            $academicYear->school_id === session('school_id');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('manage-academic-years') &&
            $academicYear->school_id === session('school_id') &&
            !$academicYear->is_current;
    }
}
