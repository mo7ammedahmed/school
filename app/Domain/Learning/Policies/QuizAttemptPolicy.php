<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizAttemptPolicy
{
    use HandlesAuthorization;

    public function view(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->hasPermissionTo('manage-quiz-attempts') ||
            $quizAttempt->school_id === session('school_id');
    }

    public function update(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->hasPermissionTo('manage-quiz-attempts') &&
            $quizAttempt->school_id === session('school_id');
    }

    public function delete(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->hasPermissionTo('manage-quiz-attempts') &&
            $quizAttempt->school_id === session('school_id');
    }
}
