<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\ReportCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportCardPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ReportCard $reportCard): bool
    {
        return $user->hasPermissionTo('manage-report-cards') ||
            $reportCard->school_id === session('school_id');
    }

    public function update(User $user, ReportCard $reportCard): bool
    {
        return $user->hasPermissionTo('manage-report-cards') &&
            $reportCard->school_id === session('school_id');
    }

    public function delete(User $user, ReportCard $reportCard): bool
    {
        return $user->hasPermissionTo('manage-report-cards') &&
            $reportCard->school_id === session('school_id');
    }
}
