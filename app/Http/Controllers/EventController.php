<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Content\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::where('school_id', session('school_id'))->latest()->paginate(15);

        return inertia('events/index', ['events' => $events]);
    }

    public function create(): Response
    {
        return inertia('events/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'required|string|max:255',
            'target_audience' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);

        $active = (int) $validated['is_active'] === 1;

        $event = Event::create([
            'school_id' => session('school_id'),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['event_date'] . ' ' . $validated['start_time'],
            'end_date' => $validated['event_date'] . ' ' . $validated['end_time'],
            'location' => $validated['location'],
            'event_type' => $validated['target_audience'] ?? 'all',
            'featured_image_path' => null,
            'is_published' => $active,
        ]);

        return redirect()->route('content.events.show', $event)->with('success', 'Event created successfully.');
    }

    public function show(Event $event): Response
    {
        $this->authorizeSchool($event);

        return inertia('events/show', ['event' => $event]);
    }

    public function edit(Event $event): Response
    {
        $this->authorizeSchool($event);

        return inertia('events/edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeSchool($event);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'required|string|max:255',
            'target_audience' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);

        $active = (int) $validated['is_active'] === 1;

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['event_date'] . ' ' . $validated['start_time'],
            'end_date' => $validated['event_date'] . ' ' . $validated['end_time'],
            'location' => $validated['location'],
            'event_type' => $validated['target_audience'] ?? 'all',
            'is_published' => $active,
        ]);

        return redirect()->route('content.events.show', $event)->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeSchool($event);
        $event->delete();

        return redirect()->route('content.events.index')->with('success', 'Event deleted successfully.');
    }

    private function authorizeSchool(Event $event): void
    {
        if ((int) $event->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }
}
