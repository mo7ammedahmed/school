<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Content\Services\SiteMetadata;
use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

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

        // The article's own name is the page's name; the middleware handles the
        // rest of the site so nothing here has to know about <head>.
        app(SiteMetadata::class)->applyPage($article->title, $article->excerpt ?? null, indexable: true);

        return Inertia::render('public/news/show', [
            'article' => $article,
        ]);
    }
}
