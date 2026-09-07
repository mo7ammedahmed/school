<?php

declare(strict_types=1);

namespace App\Domain\Documents\Policies;

use App\Domain\Documents\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage-documents') ||
            $document->school_id === session('school_id');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage-documents') &&
            $document->school_id === session('school_id');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage-documents') &&
            $document->school_id === session('school_id');
    }
}
