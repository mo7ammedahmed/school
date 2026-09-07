<?php

declare(strict_types=1);

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\ContactLead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactLeadPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ContactLead $model): bool
    {
        return $user->hasPermissionTo('manage-contact-leads') ||
            $model->school_id === session('school_id');
    }

    public function update(User $user, ContactLead $model): bool
    {
        return $user->hasPermissionTo('manage-contact-leads') &&
            $model->school_id === session('school_id');
    }

    public function delete(User $user, ContactLead $model): bool
    {
        return $user->hasPermissionTo('manage-contact-leads') &&
            $model->school_id === session('school_id');
    }
}
