<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\ExamResult;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamResultPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ExamResult $examResult): bool
    {
        return $user->hasPermissionTo('manage-exam-results') ||
            $examResult->school_id === session('school_id');
    }

    public function update(User $user, ExamResult $examResult): bool
    {
        return $user->hasPermissionTo('manage-exam-results') &&
            $examResult->school_id === session('school_id');
    }

    public function delete(User $user, ExamResult $examResult): bool
    {
        return $user->hasPermissionTo('manage-exam-results') &&
            $examResult->school_id === session('school_id');
    }
}
