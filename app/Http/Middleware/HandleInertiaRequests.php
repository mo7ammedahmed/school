<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Schools\Models\School;
use Illuminate\Http\Request;
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

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'school' => $schoolId ? School::find($schoolId)?->only(['id', 'name', 'locale', 'timezone', 'currency']) : null,
                ] : null,
            ],
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







