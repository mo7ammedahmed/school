<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\FeeStructure;
use App\Models\User;

/**
 * Who may read or change a fee structure.
 *
 * The policy used `hasPermissionTo('manage-fee-structures')`, which threw
 * `PermissionDoesNotExist` when the permission had not been seeded — so `/show`
 * and `/edit` were a 500 for every user. That permission is now seeded, so the
 * throw is gone and the permission check can be required rather than treated as
 * a fallback.
 *
 * update() and delete() consulted the school alone, so any member of the school
 * could pass the gate with no permission to change a fee structure at all. Every
 * method now requires the permission AND the school, matching `StudentPolicy`.
 */
class FeeStructurePolicy
{
    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') &&
            $this->owns($feeStructure);
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') &&
            $this->owns($feeStructure);
    }

    public function delete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->hasPermissionTo('manage-fee-structures') &&
            $this->owns($feeStructure);
    }

    private function owns(FeeStructure $feeStructure): bool
    {
        $schoolId = (int) session('school_id');

        return $schoolId !== 0 && (int) $feeStructure->school_id === $schoolId;
    }
}
