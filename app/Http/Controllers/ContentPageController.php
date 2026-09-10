<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Content\Models\ContentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function index(): Response
    {
        return inertia('content/pages/index', [
            'pages' => ContentPage::where('school_id', session('school_id'))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return inertia('content/pages/create', [
            'sectionTypes' => $this->sectionTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = ContentPage::create($this->validated($request) + [
            'school_id' => session('school_id'),
        ]);

        return redirect()->route('pages.edit', $page)->with('success', 'Page created successfully.');
    }

    public function edit(ContentPage $page): Response
    {
        $this->authorizeSchool($page);

        return inertia('content/pages/edit', [
            'page' => $page,
            'sectionTypes' => $this->sectionTypes(),
        ]);
    }

    public function update(Request $request, ContentPage $page): RedirectResponse
    {
        $this->authorizeSchool($page);
        $page->update($this->validated($request, $page));

        return redirect()->route('pages.edit', $page)->with('success', 'Page updated successfully.');
    }

    public function destroy(ContentPage $page): RedirectResponse
    {
        $this->authorizeSchool($page);
        $page->delete();

        return redirect()->route('pages.index')->with('success', 'Page archived.');
    }

    private function validated(Request $request, ?ContentPage $page = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255'],
            'content' => ['nullable', 'string'],
            'template' => ['required', 'in:standard,landing'],
            'sections' => ['nullable', 'array', 'max:30'],
            'sections.*.type' => ['required', 'string', 'in:hero,rich_text,news,events,staff,faq,cta'],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.content' => ['nullable', 'array'],
            'sections.*.settings' => ['nullable', 'array'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
        ]);

        $status = $validated['status'];
        $validated['is_published'] = $status === 'published';
        $validated['published_at'] = $status === 'published'
            ? ($validated['published_at'] ?? $page?->published_at ?? now())
            : null;

        return $validated + ['seo_metadata' => []];
    }

    private function authorizeSchool(ContentPage $page): void
    {
        abort_unless((int) $page->school_id === (int) session('school_id'), 403);
    }

    private function sectionTypes(): array
    {
        return [
            ['value' => 'hero', 'label' => 'Hero'],
            ['value' => 'rich_text', 'label' => 'Rich text'],
            ['value' => 'news', 'label' => 'News'],
            ['value' => 'events', 'label' => 'Events'],
            ['value' => 'staff', 'label' => 'Staff'],
            ['value' => 'faq', 'label' => 'FAQ'],
            ['value' => 'cta', 'label' => 'Call to action'],
        ];
    }
}
