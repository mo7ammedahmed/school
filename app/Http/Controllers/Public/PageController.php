<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Services\SiteMetadata;
use Inertia\Response;
use Laravel\Head\Facades\Head;

class PageController
{
    public function show(string $slug): Response
    {
        $page = ContentPage::published()
            ->where('slug', $slug)
            ->with(['school' => function ($query) {
                $query->select([
                    'id',
                    'primary_color',
                    'secondary_color',
                    'accent_color',
                    'logo_path',
                    'favicon_path',
                    'metadata',
                ]);
            }])
            ->firstOrFail();

        // The CMS page owns its own SEO copy, so it sets it at runtime rather
        // than through a reactive <Head> element in the React page.
        app(SiteMetadata::class)->applyPage(
            $page->seo_title ?: $page->title,
            $page->seo_description,
            indexable: ($page->robots ?? 'index,follow') === 'index,follow',
        );

        if ($page->canonical_url) {
            Head::canonical($page->canonical_url);
        }

        return inertia('public/page', [
            'page' => $page->only([
                'title',
                'title_ar',
                'content',
                'sections',
                'seo_title',
                'seo_description',
                'canonical_url',
                'robots',
            ]),
            'school' => $page->school ? [
                'primary_color' => $page->school->primary_color,
                'secondary_color' => $page->school->secondary_color,
                'accent_color' => $page->school->accent_color,
                'logo_path' => $page->school->logo_path,
                'favicon_path' => $page->school->favicon_path,
                'theme_config' => $page->school->getThemeConfig(),
            ] : null,
        ]);
    }
}
