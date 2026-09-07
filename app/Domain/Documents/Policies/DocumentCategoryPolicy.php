<?php

declare(strict_types=1);

namespace App\Domain\Documents\Policies;

use App\Domain\Documents\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentCategoryPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->hasPermissionTo('manage-document-categories') ||
            $documentCategory->school_id === session('school_id');
    }

    public function update(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->hasPermissionTo('manage-document-categories') &&
            $documentCategory->school_id === session('school_id');
    }

    public function delete(User $user, DocumentCategory $documentCategory): bool
    {
        return $user->hasPermissionTo('manage-document-categories') &&
            $documentCategory->school_id === session('school_id');
    }
}
