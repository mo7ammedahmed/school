<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('manage-exams') ||
            $exam->school_id === session('school_id');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('manage-exams') &&
            $exam->school_id === session('school_id');
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('manage-exams') &&
            $exam->school_id === session('school_id');
    }
}
