<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Policies;

use App\Domain\Compliance\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AuditLog $model): bool
    {
        return $user->hasPermissionTo('view-audit-logs') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, AuditLog $model): bool
    {
        return $user->hasPermissionTo('view-audit-logs') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, AuditLog $model): bool
    {
        return $user->hasPermissionTo('view-audit-logs') &&
            $model->school_id === session('school_id');
    }
}
