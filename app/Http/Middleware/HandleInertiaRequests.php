<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Schools\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $schoolId = session('school_id');
        $school = $schoolId ? School::find($schoolId) : null;

        // Public (guest) requests have no school session: fall back to the
        // default school so the marketing pages still apply its branding.
        // Cache the id only — serialising models into the cache store
        // unserialises as an incomplete object on the next request.
        if ($school === null && $user === null) {
            $defaultSchoolId = Cache::remember(
                'school.default',
                now()->addMinutes(5),
                fn () => School::query()->orderBy('id')->value('id'),
            );
            $school = $defaultSchoolId ? School::find($defaultSchoolId) : null;
        }

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
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'school' => $school ? $this->safeSchool($school) : null,
                ] : null,
            ],
            'school' => $school ? $this->safeSchool($school) : null,
            'appearance' => array_merge(
                $appearance,
                [
                    'theme' => $user?->theme ?? 'system',
                ]
            ),
            'themeConfig' => $school ? $school->getThemeConfig() : [],
            'navLabels' => $school ? $school->navigationLabels()
                ->get(['key', 'name_en', 'name_ar'])
                ->mapWithKeys(fn ($label) => [
                    $label->key => ['en' => $label->name_en, 'ar' => $label->name_ar],
                ])
                ->all() : [],
            'locale' => $school?->locale ?? 'en',
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                'info' => session('info'),
            ],
            'csrf_token' => $request->session()->token(),
        ]);
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
