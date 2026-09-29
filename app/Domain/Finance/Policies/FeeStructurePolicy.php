<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\FeeStructure;
use App\Models\User;

/**
 * Who may read or change a fee structure.
 *
 * The policy used `hasPermissionTo('manage-fee-structures')`, which throws
 * `PermissionDoesNotExist` when the permission has not been seeded — so `/show`
 * and `/edit` were a 500 for every user, and the tenancy half of the check never
 * ran at all. `checkPermissionTo` answers false for a permission that does not
 * exist, and the school comparison is what actually guards the record.
 */
class FeeStructurePolicy
{
    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $this->owns($feeStructure) || $user->checkPermissionTo('manage-fee-structures');
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $this->owns($feeStructure);
    }

    public function delete(User $user, FeeStructure $feeStructure): bool
    {
        return $this->owns($feeStructure);
    }

    private function owns(FeeStructure $feeStructure): bool
    {
        $schoolId = (int) session('school_id');

        return $schoolId !== 0 && (int) $feeStructure->school_id === $schoolId;
    }
}
