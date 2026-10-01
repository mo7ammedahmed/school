<?php

declare(strict_types=1);

namespace App\Domain\Communication\Policies;

use App\Domain\Communication\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read a conversation.
 *
 * A `direct` conversation is only its participants' business. Before this the
 * policy could ask nothing but the permission and the school, and
 * `manage-messages` — and therefore `manage-conversations` — is on every staff
 * role's plate, so every teacher could read every thread, including messages
 * naming individual children.
 *
 * An `internal` conversation keeps its existing meaning: the school's own
 * board. That is what the messages screen creates (`MessageController::store`)
 * and what the form offers — "Start a school conversation", with no recipient
 * picker — so hiding those from staff would break the feature rather than
 * harden it. A platform super admin still bypasses every policy through
 * `Gate::before`; there is no school-scoped override, deliberately, so a school
 * administrator cannot read a colleague's direct thread without being added to
 * it.
 */
class ConversationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') &&
            $conversation->school_id === session('school_id') &&
            $this->isAddressedTo($user, $conversation);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') &&
            $conversation->school_id === session('school_id') &&
            $this->isAddressedTo($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage-conversations') &&
            $conversation->school_id === session('school_id') &&
            $this->isAddressedTo($user, $conversation);
    }

    /**
     * The audience half of the rule, kept out of the three abilities so each
     * one still states its permission and its tenant explicitly — the
     * conjunctive guard in `PolicyAbilitiesAreConjunctiveTest` reads the
     * ability's own body, and a helper that owned the whole return would hide
     * those two conditions from it.
     */
    private function isAddressedTo(User $user, Conversation $conversation): bool
    {
        return $conversation->type === 'internal'
            || $conversation->participants()->where('users.id', $user->id)->exists();
    }
}
