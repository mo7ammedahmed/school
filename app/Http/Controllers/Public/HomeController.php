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
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'excerpt', 'published_at', 'featured_image_path']);

        $upcomingEvents = Event::where('is_published', true)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(3)
            ->get(['id', 'title', 'description', 'start_date', 'end_date', 'location', 'featured_image_path']);

        return Inertia::render('welcome', [
            'latestNews' => $latestNews,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}