<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Services\PublicWebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function index(Request $request, PublicWebsiteContent $website): Response
    {
        $builtIns = ContentPage::forSchool($this->schoolId())->whereIn('slug', array_column($website->locations(), 'slug'))
            ->get(['id', 'slug', 'status'])->keyBy('slug');

        return inertia('content/pages/index', [
            'locations' => collect($website->locations())->map(fn (array $location) => $location + [
                'page' => $builtIns->get($location['slug']),
            ])->all(),
            'pages' => ContentPage::forSchool($this->schoolId())
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.mb_substr($request->string('search')->toString(), 0, 100).'%'))
                ->latest()
                ->paginate(15, ['id', 'title', 'title_ar', 'slug', 'status', 'show_in_navigation', 'updated_at'])
                ->withQueryString()
                ->through(fn (ContentPage $page) => $page->toArray() + ['url' => $website->url($page->slug)]),
        ]);
    }

    public function create(Request $request, PublicWebsiteContent $website): Response
    {
        return inertia('content/pages/create', [
            'sectionTypes' => $this->sectionTypes(),
            'initialPage' => $website->starter($request->string('location')->toString()),
            'locations' => $website->locations(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = ContentPage::create($this->validated($request) + [
            'school_id' => $this->schoolId(),
        ]);

        return redirect()->route('content.pages.edit', $page)->with('success', 'Page created successfully.');
    }

    public function edit(ContentPage $page): Response
    {
        $this->authorize('update', $page);

        // One form for both jobs: `content/pages/create` already submits a PUT to
        // the page when it is given one, and there is no `content/pages/edit`
        // component for this name to resolve to.
        return inertia('content/pages/create', [
            'page' => $page,
            'sectionTypes' => $this->sectionTypes(),
            'locations' => app(PublicWebsiteContent::class)->locations(),
            'publicUrl' => app(PublicWebsiteContent::class)->url($page->slug),
        ]);
    }

    public function update(Request $request, ContentPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $page->update($this->validated($request, $page));

        return redirect()->route('content.pages.edit', $page)->with('success', 'Page updated successfully.');
    }

    public function destroy(ContentPage $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        $page->delete();

        return redirect()->route('content.pages.index')->with('success', 'Page archived.');
    }

    public function preview(ContentPage $page, PublicWebsiteContent $website): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $page);

        $response = $website->render($page, preview: true)->toResponse(request());
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex,nofollow');

        return $response;
    }

    private function validated(Request $request, ?ContentPage $page = null): array
    {
        $publicUrl = static function (string $attribute, mixed $value, \Closure $fail): void {
            $local = str_starts_with($value, '/') && ! str_starts_with($value, '//');
            $remote = strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https'
                && filled(parse_url($value, PHP_URL_HOST));
            foreach (str_split($value) as $character) {
                if (ord($character) <= 32 || $character === '\\') {
                    $fail('Use a local path or a valid HTTPS URL without spaces or backslashes.');

                    return;
                }
            }
            if (! $local && ! $remote) {
                $fail('Use a local path or a valid HTTPS URL.');
            }
        };
        $validated = $request->validate([
            // Either language is enough: the other side is translated for the
            // operator, so an Arabic-first author never has to type English.
            'title' => ['nullable', 'string', 'max:255', 'required_without:title_ar'],
            'title_ar' => ['nullable', 'string', 'max:255', 'required_without:title'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('content_pages', 'slug')->where('school_id', $this->schoolId())->ignore($page?->id)],
            'content' => ['nullable', 'string', 'max:50000'],
            'content_ar' => ['nullable', 'string', 'max:50000'],
            'show_in_navigation' => ['sometimes', 'boolean'],
            'navigation_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'template' => ['required', 'in:standard,landing'],
            'sections' => ['nullable', 'array', 'max:30'],
            'sections.*' => ['array:type,enabled,content,settings'],
            'sections.*.type' => ['required', Rule::in(PublicWebsiteContent::TYPES)],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.content' => ['nullable', 'array:title,title_ar,description,description_ar,button_label,button_label_ar,button_url,items'],
            'sections.*.content.title' => ['nullable', 'string', 'max:255'],
            'sections.*.content.title_ar' => ['nullable', 'string', 'max:255'],
            'sections.*.content.description' => ['nullable', 'string', 'max:10000'],
            'sections.*.content.description_ar' => ['nullable', 'string', 'max:10000'],
            'sections.*.content.button_label' => ['nullable', 'string', 'max:100'],
            'sections.*.content.button_label_ar' => ['nullable', 'string', 'max:100'],
            'sections.*.content.button_url' => ['bail', 'nullable', 'string', 'max:2048', $publicUrl],
            'sections.*.content.items' => ['nullable', 'array', 'max:12'],
            'sections.*.content.items.*' => ['array:title,title_ar,description,description_ar,url'],
            'sections.*.content.items.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.content.items.*.title_ar' => ['nullable', 'string', 'max:255'],
            'sections.*.content.items.*.description' => ['nullable', 'string', 'max:5000'],
            'sections.*.content.items.*.description_ar' => ['nullable', 'string', 'max:5000'],
            'sections.*.content.items.*.url' => ['bail', 'nullable', 'string', 'max:2048', $publicUrl],
            'sections.*.settings' => ['nullable', 'array:limit'],
            'sections.*.settings.limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
        ]);

        // Pages store a single Arabic title column alongside the English one.

        $status = $validated['status'];
        $validated['is_published'] = $status === 'published';
        $validated['published_at'] = $status === 'published'
            ? ($validated['published_at'] ?? $page?->published_at ?? now())
            : null;

        return $validated;
    }

    private function sectionTypes(): array
    {
        return [
            ['value' => 'hero', 'label' => 'Hero'],
            ['value' => 'rich_text', 'label' => 'Rich text'],
            ['value' => 'features', 'label' => 'Features'],
            ['value' => 'stats', 'label' => 'Facts and figures'],
            ['value' => 'programs', 'label' => 'Academic programs'],
            ['value' => 'news', 'label' => 'News'],
            ['value' => 'events', 'label' => 'Events'],
            ['value' => 'staff', 'label' => 'Staff'],
            ['value' => 'faq', 'label' => 'FAQ'],
            ['value' => 'cta', 'label' => 'Call to action'],
        ];
    }
}
