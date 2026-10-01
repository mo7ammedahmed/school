<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Communication\Models\Conversation;
use App\Domain\Communication\Models\Message;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * A conversation typed `direct` was readable by every teacher in the school.
 * The word promised a private channel; the schema recorded no participants at
 * all, so `ConversationPolicy` had nothing to check but the permission, and
 * `manage-messages` is on every staff role's plate.
 *
 * The fix gives the word something to mean. A `direct` conversation is its
 * participants' business: the policy checks membership, the list stops showing
 * conversations the caller is not in, and the author joins on creation. An
 * `internal` conversation keeps its existing meaning — the school's own board,
 * which is what `MessageController` creates and what `messages/create.tsx`
 * ("Start a school conversation", no recipient picker) actually offers.
 *
 * Legacy `direct` rows have no participants to find, so they fail closed until
 * an operator runs the backfill, which derives the audience from the people who
 * actually wrote in the thread (dry-run first, because a guess about who should
 * be able to read a conversation is worth reviewing before it is written).
 */
class ConversationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const STAFF_PERMISSIONS = ['manage-messages', 'manage-conversations'];

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        foreach (self::STAFF_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_a_staff_member_cannot_open_a_direct_conversation_they_are_not_in(): void
    {
        $participant = $this->staff();
        $viewer = $this->signedInStaff();

        $conversation = $this->conversation('direct');
        $conversation->participants()->attach($participant->id);

        $this->assertNotSame($participant->id, $viewer->id);

        $this->get("/messages/{$conversation->id}")->assertForbidden();
    }

    public function test_the_message_list_hides_direct_conversations_the_caller_is_not_in(): void
    {
        $participant = $this->staff();
        $this->signedInStaff();

        $conversation = $this->conversation('direct');
        $conversation->participants()->attach($participant->id);

        $this->get('/messages')->assertOk()->assertInertia(function ($page) use ($conversation): void {
            $ids = collect($page->toArray()['props']['conversations']['data'] ?? [])->pluck('id')->all();

            $this->assertNotContains(
                $conversation->id,
                $ids,
                'The list showed a direct conversation to somebody who is not in it, and its last message '
                .'with it.',
            );
        });
    }

    public function test_the_message_list_still_shows_the_schools_internal_board(): void
    {
        $this->staff();
        $this->signedInStaff();

        $conversation = $this->conversation('internal');

        $this->get('/messages')->assertOk()->assertInertia(function ($page) use ($conversation): void {
            $ids = collect($page->toArray()['props']['conversations']['data'] ?? [])->pluck('id')->all();

            $this->assertContains(
                $conversation->id,
                $ids,
                'An internal conversation is the school board and every staff member may see it; the '
                .'direct-conversation filter must not hide it.',
            );
        });
    }

    public function test_a_participant_can_open_their_direct_conversation(): void
    {
        $participant = $this->signedInStaff();

        $conversation = $this->conversation('direct');
        $conversation->participants()->attach($participant->id);

        $this->get("/messages/{$conversation->id}")->assertSuccessful();
    }

    public function test_an_internal_conversation_is_still_open_to_every_staff_member(): void
    {
        $this->staff();
        $this->signedInStaff();

        $conversation = $this->conversation('internal');

        $this->get("/messages/{$conversation->id}")->assertSuccessful();
    }

    public function test_a_direct_conversation_with_no_participants_is_open_to_nobody(): void
    {
        $sender = $this->signedInStaff();

        $conversation = $this->conversation('direct');
        $this->message($conversation, $sender, 'A message in a legacy thread.');

        // Until the backfill runs nobody is recorded, and a sender is not a
        // participant by implication. Fail closed is the only safe answer.
        $this->get("/messages/{$conversation->id}")->assertForbidden();
    }

    public function test_creating_a_conversation_attaches_its_author(): void
    {
        $author = $this->signedInStaff();

        $this->post('/messages', [
            'subject' => 'Staff meeting reminders',
            'body' => 'Please arrive by eight.',
        ])->assertRedirect();

        $conversation = Conversation::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $author->id,
        ]);

        $this->get("/messages/{$conversation->id}")->assertSuccessful();
    }

    // ------------------------------------------------------------------
    // The backfill
    // ------------------------------------------------------------------

    public function test_the_backfill_attaches_every_sender_and_a_dry_run_writes_nothing(): void
    {
        $first = $this->staff();
        $second = $this->staff();

        $this->signedInStaff();

        $conversation = $this->conversation('direct');
        $this->message($conversation, $first, 'One.');
        $this->message($conversation, $second, 'Two.');

        $this->artisan('conversations:backfill-participants', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('conversation_participants', 0);

        $this->artisan('conversations:backfill-participants')
            ->assertSuccessful();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $first->id,
        ]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $second->id,
        ]);
    }

    public function test_the_backfill_leaves_a_conversation_with_no_messages_alone(): void
    {
        $this->signedInStaff();

        $conversation = $this->conversation('direct');

        $this->artisan('conversations:backfill-participants')->assertSuccessful();

        $this->assertDatabaseCount('conversation_participants', 0);
        $this->assertSame(0, $conversation->participants()->count());
    }

    // ------------------------------------------------------------------

    /**
     * A staff member with the permissions the messages screen and its policy
     * ask for, without signing them in.
     */
    private function staff(): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo(self::STAFF_PERMISSIONS);

        return $user;
    }

    private function signedInStaff(): User
    {
        $user = $this->actingAsSchoolUser($this->school, self::STAFF_PERMISSIONS);
        $this->app['session']->put('school_id', $this->school->id);

        return $user;
    }

    private function conversation(string $type): Conversation
    {
        return Conversation::create([
            'school_id' => $this->school->id,
            'type' => $type,
            'subject' => ucfirst($type).' conversation',
        ]);
    }

    private function message(Conversation $conversation, User $sender, string $body): Message
    {
        return Message::create([
            'school_id' => $this->school->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }
}
