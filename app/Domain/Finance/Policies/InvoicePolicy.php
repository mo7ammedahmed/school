<?php

declare(strict_types=1);

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') ||
            $invoice->school_id === session('school_id');
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            $invoice->status === 'draft';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            $invoice->status === 'draft';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            $invoice->status === 'draft';
    }
}
