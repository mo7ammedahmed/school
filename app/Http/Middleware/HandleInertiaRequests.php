<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Content\Models\ContentPage;
use App\Domain\Content\Services\PublicWebsiteContent;
use App\Domain\Identity\Services\SharedPermissionList;
use App\Domain\Localization\Services\InterfaceCatalog;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolNavigationLabel;
use App\Domain\Schools\Services\SchoolResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly SchoolResolver $schools,
        private readonly InterfaceCatalog $interfaceCatalog,
        private readonly SharedPermissionList $permissions,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Public (guest) requests have no school session: the resolver falls
        // back to the default school so the marketing pages still apply its
        // branding. A signed-in user without a school selected gets nothing.
        $school = $this->schools->current(
            (int) session('school_id') ?: null,
            allowFallback: $user === null,
        );

        // Cache the appearance fields for the frontend
        $appearance = $school ? Cache::remember(
            "school-appearance:{$school->id}",
            now()->addMinutes(10),
            fn () => $school->only([
                'logo_path',
                'favicon_path',
                'primary_color',
                'secondary_color',
                'accent_color',
            ]),
        ) : [
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => null,
            'secondary_color' => null,
            'accent_color' => null,
        ];

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $this->permissions->for($user),
                    'school' => $school ? $this->safeSchool($school) : null,
                ] : null,
            ],
            'school' => $school ? $this->safeSchool($school) : null,
            'publicSchoolContact' => $school?->only(['email', 'phone', 'address', 'address_ar']),
            'websiteNavigation' => fn (): array => $school ? ContentPage::forSchool($school->id)->published()
                ->where('show_in_navigation', true)->orderBy('navigation_order')->orderBy('title')->limit(12)
                ->get(['slug', 'title', 'title_ar'])->map(fn (ContentPage $page) => [
                    'title' => $page->title, 'title_ar' => $page->title_ar,
                    'url' => app(PublicWebsiteContent::class)->url($page->slug),
                ])->all() : [],
            'appearance' => array_merge(
                $appearance,
                [
                    'theme' => $user?->theme ?? 'system',
                ]
            ),
            'themeConfig' => $school ? $school->getThemeConfig() : [],
            // Light/dark headline palettes, editable in Settings → Theme.
            'themeModes' => $school ? $school->getThemeModes() : null,
            // No column list here: a partial select hides the row's model class
            // from the analyser and turns the mapping below untyped.
            'navLabels' => $school ? $school->navigationLabels()
                ->get()
                ->mapWithKeys(fn (SchoolNavigationLabel $label): array => [
                    $label->key => ['en' => $label->name_en, 'ar' => $label->name_ar],
                ])
                ->all() : [],
            'locale' => app()->getLocale(),
            // The dashboard's Arabic dictionary, sent once per version rather
            // than re-requested six strings at a time while the page loads.
            'uiCopy' => fn (): ?array => $this->interfaceCopy($request),
            // Only the two severities anything actually flashes: no controller
            // ever set a `warning` or an `info`, and no page read one.
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'csrf_token' => $request->session()->token(),
        ]);
    }

    /**
     * The interface dictionary, when this request still needs it.
     *
     * The version is remembered in a cookie so later navigations send nothing at
     * all; the browser then repaints from what it already stored.
     */
    private function interfaceCopy(Request $request): ?array
    {
        if (! $this->interfaceCatalog->shouldShare($request)) {
            return null;
        }

        $payload = $this->interfaceCatalog->payload();

        Cookie::queue(InterfaceCatalog::COOKIE, $payload['version'], 60 * 24 * 365);

        return $payload;
    }

    /**
     * A safe, serialisable school payload for the client — never the raw model.
     */
    private function safeSchool(School $school): array
    {
        return $school->only([
            'id',
            'slug',
            'locale',
            'timezone',
            'currency',
            'name_ar',
            'name_en',
            'logo_path',
            'favicon_path',
            'primary_color',
            'secondary_color',
            'accent_color',
        ]) + [
            'name' => $school->name,
            'theme_config' => $school->getThemeConfig(),
        ];
    }
}
