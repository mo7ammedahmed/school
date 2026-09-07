<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Content\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class NewsController extends Controller
{
    public function index(): Response
    {
        $news = News::where('school_id', session('school_id'))->latest()->paginate(15);

        return inertia('news/index', ['news' => $news]);
    }

    public function create(): Response
    {
        return inertia('news/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'publish_date' => 'nullable|date',
            'is_published' => 'required|in:0,1',
        ]);

        $published = (int) $validated['is_published'] === 1;

        $article = News::create([
            'school_id' => session('school_id'),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(4)),
            'excerpt' => Str::limit(strip_tags($validated['content']), 160),
            'content' => $validated['content'],
            'seo_metadata' => [],
            'is_published' => $published,
            'published_at' => $published ? ($validated['publish_date'] ?: now()) : null,
        ]);

        return redirect()->route('content.news.show', $article)->with('success', 'Article created successfully.');
    }

    public function show(News $news): Response
    {
        $this->authorizeSchool($news);

        return inertia('news/show', ['article' => $news]);
    }

    public function edit(News $news): Response
    {
        $this->authorizeSchool($news);

        return inertia('news/edit', ['article' => $news]);
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $this->authorizeSchool($news);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'publish_date' => 'nullable|date',
            'is_published' => 'required|in:0,1',
        ]);

        $published = (int) $validated['is_published'] === 1;

        $news->update([
            'title' => $validated['title'],
            'excerpt' => Str::limit(strip_tags($validated['content']), 160),
            'content' => $validated['content'],
            'is_published' => $published,
            'published_at' => $published ? ($validated['publish_date'] ?: $news->published_at ?? now()) : null,
        ]);

        return redirect()->route('content.news.show', $news)->with('success', 'Article updated successfully.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->authorizeSchool($news);
        $news->delete();

        return redirect()->route('content.news.index')->with('success', 'Article deleted successfully.');
    }

    private function authorizeSchool(News $news): void
    {
        if ((int) $news->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }
}
