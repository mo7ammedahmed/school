<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Schools\Models\School;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Cache;

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

        // Cache the appearance fields for the frontend
        $appearance = $school ? Cache::remember(
            "school-appearance:{$school->id}",
            now()->addMinutes(10),
            fn () => $school->only([
                'logo_path',
                'favicon_path',
                'primary_color',
                'secondary_color',
            ]),
        ) : [
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => null,
            'secondary_color' => null,
        ];

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'school' => $school, // Keep the School model instance for backend use
                ] : null,
            ],
            'appearance' => array_merge(
                $appearance,
                [
                    'theme' => $user?->theme ?? 'system',
                ]
            ),
            'locale' => $user && $user->currentMembership?->school?->locale ?? 'en',
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                'info' => session('info'),
            ],
            'csrf_token' => $request->session()->token(),
        ]);
    }
}






