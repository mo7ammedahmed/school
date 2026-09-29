<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Event;
use App\Domain\Content\Services\SiteMetadata;
use Inertia\Inertia;
use Inertia\Response;

class EventsController
{
    public function index(): Response
    {
        // `event_date` is an appended display name for `start_date`. As a query
        // column it does not exist, so the list filtered and sorted on a string
        // literal and returned nothing.
        $events = Event::where('is_published', true)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('public/events/index', [
            'events' => $events,
        ]);
    }

    public function show(int $id): Response
    {
        // A published event keeps its page after the date passes; only the
        // listing is limited to what is upcoming.
        $event = Event::where('is_published', true)->findOrFail($id);

        app(SiteMetadata::class)->applyPage($event->title, $event->description ?? null, indexable: true);

        return Inertia::render('public/events/show', [
            'event' => $event,
        ]);
    }
}
