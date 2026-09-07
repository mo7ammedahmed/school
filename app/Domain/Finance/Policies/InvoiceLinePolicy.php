<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoiceLinePolicy
{
    use HandlesAuthorization;

    public function view(User $user, InvoiceLine $model): bool
    {
        return $user->hasPermissionTo('manage-invoice-lines') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, InvoiceLine $model): bool
    {
        return $user->hasPermissionTo('manage-invoice-lines') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, InvoiceLine $model): bool
    {
        return $user->hasPermissionTo('manage-invoice-lines') &&
            $model->school_id === session('school_id');
    }
}
