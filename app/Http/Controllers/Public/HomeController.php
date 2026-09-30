<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Event;
use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends PublicController
{
    public function index(): Response
    {
        $schoolId = $this->schoolId();

        $latestNews = News::forSchool($schoolId)
            ->where('is_published', true)
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'excerpt', 'published_at', 'featured_image_path']);

        $upcomingEvents = Event::forSchool($schoolId)
            ->where('is_published', true)
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
