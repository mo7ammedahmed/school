<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Academics\Models\Subject;
use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Models\Event;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\News;
use App\Domain\Content\Models\StaffProfile;
use Inertia\Response;
use Laravel\Head\Facades\Head;

final class PublicWebsiteContent
{
    public const TYPES = ['hero', 'rich_text', 'features', 'stats', 'news', 'events', 'programs', 'staff', 'faq', 'cta'];

    /** Existing public URLs stay stable when their content moves into the CMS. */
    public function locations(): array
    {
        return [
            ['slug' => 'home', 'url' => '/', 'title' => 'Home', 'title_ar' => 'الرئيسية'],
            ['slug' => 'about', 'url' => '/about', 'title' => 'About the school', 'title_ar' => 'عن المدرسة'],
            ['slug' => 'programs', 'url' => '/programs', 'title' => 'Academic programs', 'title_ar' => 'البرامج التعليمية'],
            ['slug' => 'admissions', 'url' => '/admissions', 'title' => 'Admissions', 'title_ar' => 'القبول والتسجيل'],
            ['slug' => 'facilities', 'url' => '/facilities', 'title' => 'Campus and facilities', 'title_ar' => 'الحرم والمرافق'],
            ['slug' => 'faculty', 'url' => '/faculty', 'title' => 'Our team', 'title_ar' => 'فريق المدرسة'],
            ['slug' => 'contact', 'url' => '/contact', 'title' => 'Contact us', 'title_ar' => 'تواصل معنا'],
            ['slug' => 'faq', 'url' => '/faq', 'title' => 'Frequently asked questions', 'title_ar' => 'الأسئلة الشائعة'],
        ];
    }

    public function url(string $slug): string
    {
        return collect($this->locations())->firstWhere('slug', $slug)['url'] ?? '/pages/'.$slug;
    }

    public function starter(string $slug): ?array
    {
        $location = collect($this->locations())->firstWhere('slug', $slug);
        if ($location === null) {
            return null;
        }
        $sections = [['type' => 'hero', 'enabled' => true, 'content' => [
            'title' => $location['title'], 'title_ar' => $location['title_ar'],
        ], 'settings' => []]];
        $collection = match ($slug) {
            'home' => 'news', 'programs' => 'programs', 'faculty' => 'staff', 'faq' => 'faq', default => null,
        };
        if ($collection !== null) {
            $sections[] = ['type' => $collection, 'enabled' => true, 'content' => [], 'settings' => ['limit' => 6]];
        }
        if (in_array($slug, ['home', 'admissions'], true)) {
            $sections[] = ['type' => 'cta', 'enabled' => true, 'content' => [
                'title' => 'Start your application', 'title_ar' => 'ابدأ طلب الالتحاق',
                'button_label' => 'Apply now', 'button_label_ar' => 'قدّم الآن', 'button_url' => '/apply',
            ], 'settings' => []];
        }

        return ['slug' => $slug, 'title' => $location['title'], 'title_ar' => $location['title_ar'],
            'template' => 'landing', 'status' => 'draft', 'robots' => 'index,follow', 'sections' => $sections];
    }

    public function published(?int $schoolId, string $slug): ?Response
    {
        $page = ContentPage::forSchool($schoolId)->published()->where('slug', $slug)->first();

        return $page ? $this->render($page) : null;
    }

    public function render(ContentPage $page, bool $preview = false): Response
    {
        $title = app()->getLocale() === 'ar' ? ($page->title_ar ?: $page->title) : $page->title;
        app(SiteMetadata::class)->applyPage($page->seo_title ?: $title, $page->seo_description,
            indexable: ! $preview && ($page->robots ?? 'index,follow') === 'index,follow');
        if ($preview) {
            Head::robots('noindex,nofollow');
        } else {
            if (request()->user() === null) {
                Head::robots($page->robots ?? 'index,follow');
            }
            if ($page->canonical_url) {
                Head::canonical($page->canonical_url);
            }
        }

        $types = collect($page->sections ?? [])->filter(fn ($section) => ($section['enabled'] ?? false) && empty($section['content']['items']))->pluck('type');
        $collections = [];
        if ($types->contains('news')) {
            $collections['news'] = News::forSchool($page->school_id)->where('is_published', true)
                ->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at')->limit(12)
                ->get(['id', 'title', 'title_ar', 'excerpt', 'excerpt_ar', 'published_at'])->map(fn (News $item) => [
                    'title' => $item->title, 'title_ar' => $item->title_ar, 'description' => $item->excerpt,
                    'description_ar' => $item->excerpt_ar, 'url' => '/news/'.$item->id,
                ])->all();
        }
        if ($types->contains('events')) {
            $collections['events'] = Event::forSchool($page->school_id)->where('is_published', true)
                ->where('start_date', '>=', now()->startOfDay())->orderBy('start_date')->limit(12)
                ->get(['id', 'title', 'title_ar', 'description', 'description_ar', 'start_date'])->map(fn (Event $item) => [
                    'title' => $item->title, 'title_ar' => $item->title_ar, 'description' => $item->description,
                    'description_ar' => $item->description_ar, 'url' => '/events/'.$item->id,
                ])->all();
        }
        if ($types->contains('programs')) {
            $collections['programs'] = Subject::forSchool($page->school_id)->orderBy('name_en')->limit(12)
                ->get(['id', 'name_en', 'name_ar', 'description'])->map(fn (Subject $item) => [
                    'title' => $item->name_en, 'title_ar' => $item->name_ar,
                    'description' => $item->description, 'url' => '/programs/'.$item->id,
                ])->all();
        }
        if ($types->contains('staff')) {
            $collections['staff'] = StaffProfile::forSchool($page->school_id)->where('is_featured', true)
                ->orderBy('sort_order')->limit(12)->get(['first_name', 'last_name', 'position', 'position_ar', 'bio', 'bio_ar'])
                ->map(fn (StaffProfile $item) => ['title' => trim($item->first_name.' '.$item->last_name),
                    'description' => $item->position."\n".$item->bio, 'description_ar' => $item->position_ar."\n".$item->bio_ar])->all();
        }
        if ($types->contains('faq')) {
            $collections['faq'] = Faq::forSchool($page->school_id)->where('is_published', true)->orderBy('sort_order')
                ->limit(12)->get(['question', 'question_ar', 'answer', 'answer_ar'])->map(fn (Faq $item) => [
                    'title' => $item->question, 'title_ar' => $item->question_ar,
                    'description' => $item->answer, 'description_ar' => $item->answer_ar,
                ])->all();
        }

        $publicPage = $page->only([
            'title', 'title_ar', 'slug', 'content', 'content_ar', 'template', 'sections',
            'seo_title', 'seo_description', 'canonical_url', 'robots',
        ]);
        // Hidden sections must not travel to a visitor's HTML or Inertia payload.
        $publicPage['sections'] = collect($page->sections ?? [])->filter(fn ($section) => $section['enabled'] ?? false)->values()->all();

        return inertia('public/page', ['page' => $publicPage, 'collections' => $collections, 'preview' => $preview,
            'editorUrl' => $preview ? '/content/pages/'.$page->id.'/edit' : null]);
    }
}
