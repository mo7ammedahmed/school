<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Schools\Models\School;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;

/**
 * Owns everything that reaches <head>: the school's identity (name, mark,
 * description, organisation schema) and the human name of the current page.
 *
 * Page names are bilingual here rather than in the views, because the title and
 * the Open Graph tags are server-rendered for crawlers and link previews — a
 * client-side translation would arrive too late for both.
 */
final class SiteMetadata
{
    /** Route-name segment => bilingual page name. */
    private const array PAGES = [
        'home' => ['en' => 'Home', 'ar' => 'الرئيسية'],
        'about' => ['en' => 'About Us', 'ar' => 'من نحن'],
        'programs' => ['en' => 'Academic Programs', 'ar' => 'البرامج الأكاديمية'],
        'admissions' => ['en' => 'Admissions', 'ar' => 'القبول والتسجيل'],
        'apply' => ['en' => 'Apply', 'ar' => 'قدّم طلبك'],
        'facilities' => ['en' => 'Facilities', 'ar' => 'المرافق'],
        'teachers' => ['en' => 'Our Teachers', 'ar' => 'هيئة التدريس'],
        'news' => ['en' => 'News', 'ar' => 'الأخبار'],
        'events' => ['en' => 'Events', 'ar' => 'الفعاليات'],
        'faq' => ['en' => 'Frequently Asked Questions', 'ar' => 'الأسئلة الشائعة'],
        'contact' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
        'pages' => ['en' => 'School Page', 'ar' => 'صفحة المدرسة'],
        // The Appearance & Theme screen: without this the Arabic title fell back
        // to Str::headline(), which is English-only.
        'appearance' => ['en' => 'Appearance & Theme', 'ar' => 'المظهر والسمة'],
        'live' => ['en' => 'Live Lessons', 'ar' => 'الدروس المباشرة'],
        'lessons' => ['en' => 'My Lessons', 'ar' => 'دروسي'],
    ];

    /** Route-name segments that are structure, not a page. */
    private const array IGNORED_SEGMENTS = ['index', 'show', 'create', 'edit', 'store', 'update', 'destroy'];

    public function locale(): string
    {
        return app()->getLocale() === 'ar' ? 'ar' : 'en';
    }

    /**
     * The site-wide identity: what a search result or a shared link shows.
     */
    public function applyIdentity(School $school): void
    {
        $locale = $this->locale();
        $logo = $this->publicUrl($school->logo_path);
        $icon = $this->publicUrl($school->favicon_path) ?? $logo;

        Head::title($school->name)
            ->description($this->descriptionFor($school))
            ->applicationName($school->name)
            // The request's own scheme: production already forces https through
            // URL::forceScheme, and a canonical that disagrees with the page it
            // sits on is worse than no canonical at all.
            ->canonical(forceHttps: false)
            ->og(
                type: OgType::Website,
                title: $school->name,
                description: $this->descriptionFor($school),
                url: url()->current(),
                siteName: $school->name,
                locale: $locale === 'ar' ? 'ar_SA' : 'en_US',
            )
            ->twitter(card: TwitterCard::SummaryWithLargeImage)
            ->searchableByRobots();

        if ($logo !== null) {
            Head::ogImage($logo, alt: $school->name);
        }

        if ($icon !== null) {
            Head::icon($icon);
            Head::appleTouchIcon($icon);
        }

        Head::schema($this->organizationSchema($school, $logo));
    }

    /**
     * A page's own metadata. The school's name is passed as the suffix so the
     * browser tab reads "Admissions — Al Noor School" rather than "Admissions".
     */
    public function applyPage(string $title, ?string $description = null, ?School $school = null, bool $indexable = true): void
    {
        $suffix = $school?->name ? ' — '.$school->name : null;

        Head::title($title, suffix: $suffix);

        if ($description !== null && trim($description) !== '') {
            Head::description(Str::limit(trim($description), 160));
        }

        if (! $indexable) {
            Head::hiddenFromRobots();
        }
    }

    /**
     * Names a page from its route (`settings.notifications-config` => "Settings
     * Notifications Config"), so every screen has a title even when nobody has
     * written one by hand.
     */
    public function applyRouteName(?string $routeName, ?School $school = null): void
    {
        $name = $this->pageNameFor($routeName);

        if ($name !== null) {
            Head::title($name, suffix: $school?->name ? ' — '.$school->name : null);
        }
    }

    public function pageNameFor(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        $segments = array_values(array_filter(
            explode('.', $routeName),
            static fn (string $segment): bool => ! in_array($segment, self::IGNORED_SEGMENTS, true),
        ));

        foreach ($segments as $segment) {
            if (isset(self::PAGES[$segment])) {
                return self::PAGES[$segment][$this->locale()];
            }
        }

        $tail = end($segments);

        return $tail === false ? null : Str::headline($tail);
    }

    private function descriptionFor(School $school): string
    {
        $description = $this->locale() === 'ar'
            ? ($school->description_ar ?: $school->description_en)
            : ($school->description_en ?: $school->description_ar);

        return $description !== null && trim($description) !== ''
            ? trim($description)
            : 'A bilingual school management platform: admissions, academics, attendance and finance in one place.';
    }

    /**
     * EducationalOrganization structured data. This is what answers engines and
     * assistants that ask "what is this school?".
     *
     * @return array<string, mixed>
     */
    private function organizationSchema(School $school, ?string $logo): array
    {
        // A hand-built schema must carry the schema.org context itself.
        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $school->name,
            'url' => url('/'),
            'logo' => $logo,
            'email' => $school->email,
            'telephone' => $school->phone,
        ]);

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $school->address,
            'addressLocality' => $school->city,
            'addressCountry' => $school->country,
        ]);

        if (count($address) > 1) {
            $schema['address'] = $address;
        }

        return $schema;
    }

    private function publicUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
