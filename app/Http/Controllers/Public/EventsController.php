<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventsController
{
    public function index(): Response
    {
        $events = Event::where('is_published', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('public/events/index', [
            'events' => $events,
        ]);
    }

    public function show(int $id): Response
    {
        $event = Event::where('is_published', true)
            ->where('event_date', '>=', now())
            ->findOrFail($id);

        return Inertia::render('public/events/show', [
            'event' => $event,
        ]);
    }
}
