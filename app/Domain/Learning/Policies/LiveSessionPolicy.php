<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\People\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may open, watch and end a live lesson.
 *
 * Staff hold `manage-live-sessions`; a student never does and watches through
 * `view-own-lessons`, which only ever matches a session whose offering belongs
 * to a section the student is enrolled in. The two abilities are deliberately
 * separate so the student portal cannot drift into the staff surface.
 *
 * Ending is narrower than viewing: the teacher who started the session or a
 * school-level admin. A principal with `manage-live-sessions` can watch the
 * board, not end somebody else's lesson — the same "oversight is read-only"
 * arrangement the assessment screens use.
 */
class LiveSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-live-sessions');
    }

    public function view(User $user, LiveSession $session): bool
    {
        if ((int) $session->school_id !== (int) session('school_id')) {
            return false;
        }

        if ($user->hasPermissionTo('manage-live-sessions')) {
            return true;
        }

        if (! $user->hasPermissionTo('view-own-lessons')) {
            return false;
        }

        return $this->studentIsEnrolled($user, $session);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-live-sessions');
    }

    /**
     * Start and end share one ability: both are "run this session", and a
     * teacher who may start a lesson may certainly stop it.
     */
    public function update(User $user, LiveSession $session): bool
    {
        if ((int) $session->school_id !== (int) session('school_id')) {
            return false;
        }

        if (! $user->hasPermissionTo('manage-live-sessions')) {
            return false;
        }

        return (int) $session->started_by === (int) $user->id
            || $user->hasAnyRole(['school_admin', 'super_admin']);
    }

    public function delete(User $user, LiveSession $session): bool
    {
        if ((int) $session->school_id !== (int) session('school_id')) {
            return false;
        }

        return $user->hasPermissionTo('manage-live-sessions')
            && $user->hasAnyRole(['school_admin', 'super_admin']);
    }

    private function studentIsEnrolled(User $user, LiveSession $session): bool
    {
        $studentId = Student::query()
            ->where('user_id', $user->id)
            ->where('school_id', $session->school_id)
            ->value('id');

        if ($studentId === null) {
            return false;
        }

        return $session->offering()
            ->whereHas('section.students', fn ($query) => $query->where('students.id', $studentId))
            ->exists();
    }
}
