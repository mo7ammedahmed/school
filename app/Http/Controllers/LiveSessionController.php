<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Learning\Models\LiveSession;
use App\Domain\Learning\Services\LiveMediaAccess;
use App\Domain\People\Models\TeacherProfile;
use App\Jobs\FinalizeLiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * The teacher's live studio.
 *
 * A session is created as a draft with a random `stream_key`; the studio page
 * publishes to MediaMTX at that key and the teacher ends the session when the
 * lesson is over, which dispatches the recording job. The MediaMTX address is
 * passed to the page, never derived on it — one config value, one place to
 * point at the live server.
 *
 * Teachers only ever see their own sessions and their own offerings to pick
 * from; school-level admins and the principal get the oversight list. The policy
 * decides the rest (who may start, who may end).
 */
class LiveSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LiveSession::class);

        $user = $request->user();

        $sessions = LiveSession::query()
            ->with(['offering.subject', 'offering.section', 'material'])
            ->when(
                ! $user->hasAnyRole(['school_admin', 'super_admin', 'principal']),
                fn ($query) => $query->where('started_by', $user->id),
            )
            ->latest()
            ->paginate(15);

        return inertia('live/index', ['sessions' => $sessions]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LiveSession::class);

        return inertia('live/create', ['offerings' => $this->offeringOptions($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LiveSession::class);

        $validated = $request->validate([
            'offering_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $offering = $this->offeringOptions($request)
            ->firstWhere('id', (int) $validated['offering_id']);

        if ($offering === null) {
            throw ValidationException::withMessages([
                'offering_id' => 'Choose one of your own offerings.',
            ]);
        }

        $session = LiveSession::create([
            'offering_id' => $offering['id'],
            'started_by' => $request->user()->id,
            'title' => $validated['title'],
            'status' => LiveSession::STATUS_DRAFT,
            'stream_key' => Str::random(40),
        ]);

        return redirect()
            ->route('live.show', $session)
            ->with('success', 'Live session created. Press “Go live” when you are ready.');
    }

    public function show(Request $request, LiveSession $liveSession): Response
    {
        $this->authorize('view', $liveSession);

        $liveSession->load(['offering.subject', 'offering.section', 'material']);

        $base = rtrim((string) config('media.webrtc_url'), '/');
        $canManage = Gate::allows('update', $liveSession);

        return inertia('live/show', [
            'session' => $liveSession,
            'canManage' => $canManage,
            'media' => [
                'configured' => $base !== '',
                'whipUrl' => $base === '' || ! $canManage ? null : $base.'/'.$liveSession->stream_key.'/whip',
                'whepUrl' => $base === '' ? null : $base.'/'.$liveSession->stream_key.'/whep',
                'publishToken' => $canManage ? app(LiveMediaAccess::class)->issue($request->user(), $liveSession, 'publish') : null,
            ],
        ]);
    }

    public function start(Request $request, LiveSession $liveSession): RedirectResponse
    {
        $this->authorize('update', $liveSession);

        if ($liveSession->status !== LiveSession::STATUS_DRAFT) {
            return back()->with('error', 'This session has already started.');
        }

        // The row flips to `live` before the browser opens the WHIP connection:
        // students should see the session the moment the teacher starts the
        // stream, and a failed publish is ended by the teacher or the stale
        // sweeper, not silently left as a draft.
        $liveSession->update([
            'status' => LiveSession::STATUS_LIVE,
            'started_at' => now(),
        ]);

        return back()->with('success', 'You are live.');
    }

    public function end(Request $request, LiveSession $liveSession): RedirectResponse
    {
        $this->authorize('update', $liveSession);

        if ($liveSession->status === LiveSession::STATUS_ENDED) {
            return back()->with('error', 'This session has already ended.');
        }

        $endedAt = now();

        $liveSession->update([
            'status' => LiveSession::STATUS_ENDED,
            'ended_at' => $endedAt,
            'duration_seconds' => $liveSession->started_at !== null
                ? (int) $liveSession->started_at->diffInSeconds($endedAt)
                : 0,
        ]);

        FinalizeLiveSession::dispatch($liveSession->id);

        return redirect()
            ->route('live.index')
            ->with('success', 'Session ended. The recording is being prepared for the subject materials.');
    }

    public function destroy(Request $request, LiveSession $liveSession): RedirectResponse
    {
        $this->authorize('delete', $liveSession);

        $liveSession->delete();

        return redirect()->route('live.index')->with('success', 'Live session removed.');
    }

    /**
     * The offerings this user may open a session for.
     *
     * A teacher's list is their own offerings and nothing else: the ownership
     * check lives here, in the query that builds the form choices, and again in
     * `store()` via the same list — a request cannot name an offering the form
     * never offered.
     *
     * @return Collection<int, array{id: int<0, max>, name: string, subject: string|null, section: string|null}>
     */
    private function offeringOptions(Request $request): Collection
    {
        $user = $request->user();

        $query = Offering::query()->with(['subject', 'section']);

        if (! $user->hasAnyRole(['school_admin', 'super_admin', 'principal'])) {
            $teacherId = TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            $query->where('teacher_id', $teacherId);
        }

        return $query->orderBy('name')->get()->map(fn (Offering $offering): array => [
            'id' => (int) $offering->id,
            'name' => (string) $offering->name,
            'subject' => $offering->subject?->name,
            'section' => $offering->section?->name,
        ]);
    }
}
