<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\News;
use Inertia\Response;
use Inertia\Inertia;

class NewsController
{
    public function index(): Response
    {
        $articles = News::where('is_published', true)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('public/news/index', [
            'articles' => $articles,
        ]);
    }

    public function show(int $id): Response
    {
        $article = News::where('is_published', true)
            ->where('publish_date', '<=', now())
            ->findOrFail($id);

        return Inertia::render('public/news/show', [
            'article' => $article,
        ]);
    }
}