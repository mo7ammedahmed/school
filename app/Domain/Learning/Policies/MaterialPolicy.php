<?php

declare(strict_types=1);

namespace App\Domain\Learning\Policies;

use App\Domain\Learning\Models\Material;
use App\Domain\People\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Materials are staff-managed documents, except for one deliberate exception:
 * a published lesson video is a student-facing resource.
 *
 * The exception is narrow, because `view` is also what the download and stream
 * routes authorize. A student passes only when all of these hold — the row
 * belongs to their school, `view-own-lessons` is granted, the material is
 * published, its `kind` is `video` or `recording`, and the student is enrolled
 * in the offering's section. A document of any kind, an unpublished draft, or
 * another section's lesson all fall through to the same refusal.
 */
class MaterialPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Material $material): bool
    {
        return $this->inSchool($material)
            && ($user->hasPermissionTo('manage-materials')
                || ($user->hasPermissionTo('view-own-lessons') && $this->studentMayWatch($user, $material)));
    }

    /**
     * The video stream endpoint. Today identical to `view` — named separately so
     * a future change to either one is a decision, not a drift.
     */
    public function stream(User $user, Material $material): bool
    {
        return $this->view($user, $material);
    }

    public function update(User $user, Material $material): bool
    {
        return $user->hasPermissionTo('manage-materials') && $this->inSchool($material);
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->hasPermissionTo('manage-materials') && $this->inSchool($material);
    }

    /**
     * The tenant half of every ability: permission says what a role may do, the
     * school says to whose rows.
     */
    private function inSchool(Material $material): bool
    {
        return (int) $material->school_id === (int) session('school_id');
    }

    private function studentMayWatch(User $user, Material $material): bool
    {
        if (! $material->is_published || ! $material->isStreamable()) {
            return false;
        }

        $studentId = Student::query()
            ->where('user_id', $user->id)
            ->where('school_id', $material->school_id)
            ->value('id');

        if ($studentId === null) {
            return false;
        }

        return $material->offering()
            ->whereHas('section.students', fn ($query) => $query->where('students.id', $studentId))
            ->exists();
    }
}
