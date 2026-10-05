<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Quiz;
use App\Domain\People\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('manage-quizzes') &&
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

    public function attempt(User $user, Quiz $quiz): bool
    {
        return (int) $quiz->school_id === (int) session('school_id')
            && $user->hasPermissionTo('take-quizzes')
            && $quiz->is_published
            && Student::where('user_id', $user->id)
                ->whereHas('enrollments', fn ($query) => $query
                    ->where('section_id', $quiz->offering?->section_id)
                    ->where('status', 'active'))
                ->exists();
    }
}
