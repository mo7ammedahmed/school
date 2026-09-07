<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('manage-quizzes') ||
            $quiz->school_id === session('school_id');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('manage-quizzes') &&
            $quiz->school_id === session('school_id');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('manage-quizzes') &&
            $quiz->school_id === session('school_id');
    }
}
