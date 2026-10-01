<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Communication\Models\Conversation;
use App\Domain\Communication\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $userId = (int) auth()->id();

        $conversations = Conversation::where('school_id', $schoolId)
            // `internal` is the school board; a `direct` conversation is only
            // its participants' business, and the list must not leak its
            // subject or its last message to everybody else (see
            // ConversationPolicy).
            ->where(function (Builder $query) use ($userId): void {
                $query->where('type', 'internal')
                    ->orWhereHas('participants', fn (Builder $participants) => $participants->where('users.id', $userId));
            })
            ->with(['messages.sender'])
            ->latest()
            ->paginate(15);

        $conversations->getCollection()->transform(function (Conversation $conversation): array {
            $messages = $conversation->messages;
            $last = $messages->first();

            return [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'type' => $conversation->type,
                'messages_count' => $messages->count(),
                'last_message' => $last ? [
                    'body' => $last->body,
                    'created_at' => $last->created_at?->toDateTimeString(),
                    'sender' => ['name' => $last->sender?->name],
                ] : null,
            ];
        });

        return inertia('messages/index', ['conversations' => $conversations]);
    }

    public function create(): Response
    {
        return inertia('messages/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $conversation = Conversation::create([
            'school_id' => $schoolId,
            'type' => 'internal',
            'subject' => $validated['subject'],
        ]);

        // The author is recorded as a participant. `internal` conversations do
        // not need the membership to be readable, but it is the truth about who
        // is in the thread and the only record a future `direct` flow can build
        // on.
        $conversation->participants()->attach(auth()->id());

        Message::create([
            'school_id' => $schoolId,
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()->route('messages.show', $conversation)->with('success', 'Message sent successfully.');
    }

    public function show(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $conversation->load(['messages.sender']);

        return inertia('messages/show', ['conversation' => $conversation]);
    }

    /** Legacy notifications endpoint — newest conversations as an activity feed. */
    public function notifications(): Response
    {
        return $this->index();
    }
}
