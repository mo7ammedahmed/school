<?php

declare(strict_types=1);

namespace App\Domain\People\Policies;

use App\Domain\People\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('manage-students') ||
            $student->school_id === session('school_id');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('manage-students') &&
            $student->school_id === session('school_id');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('manage-students') &&
            $student->school_id === session('school_id') &&
            $student->status !== 'graduated';
    }
}
