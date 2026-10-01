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
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id');
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            $invoice->status === 'draft';
    }

    /**
     * A partially paid invoice is still editable.
     *
     * The update path deliberately keeps whatever has been collected and only
     * recomputes the balance, and a feature test pins that behaviour
     * (`InvoiceTest::test_editing_a_partially_paid_invoice_keeps_it_partially_paid`),
     * so "draft only" was the stale half of this rule. `issued`, `paid` and
     * `voided` stay locked.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            in_array($invoice->status, ['draft', 'partially_paid'], true);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('manage-invoices') &&
            $invoice->school_id === session('school_id') &&
            $invoice->status === 'draft';
    }
}
