<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super Admin gate. Platform support access must additionally go through
        // the audited support-access flow; this gate only bypasses per-school
        // policy checks for platform operators.
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
