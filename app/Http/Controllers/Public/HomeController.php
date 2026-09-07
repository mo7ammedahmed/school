<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\News;
use App\Models\Event;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Http\Request;

class HomeController
{
    public function index(Request $request): Response
    {
        $latestNews = News::where('is_published', true)
            ->latest('publish_date')
            ->take(3)
            ->get(['id', 'title', 'excerpt', 'category', 'publish_date', 'featured_image']);

        $upcomingEvents = Event::where('is_published', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->take(3)
            ->get(['id', 'title', 'excerpt', 'event_date', 'event_time', 'location', 'featured_image']);

        return Inertia::render('welcome', [
            'latestNews' => $latestNews,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}