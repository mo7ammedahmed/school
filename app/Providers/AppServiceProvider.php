<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Laravel\Head\Enums\OgType;

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

        // Laravel Head defaults for SEO and geo optimization
        Head::defaults(fn (HeadBuilder $head) => $head
            ->title('Al Noor School', suffix: ' - Al Noor School')
            ->description('Empowering education with Islamic values and academic excellence.')
            ->canonical()
            ->og(
                type: OgType::Website,
                title: 'Al Noor School',
                description: 'Empowering education with Islamic values and academic excellence.',
                siteName: 'Al Noor School'
            )
            ->searchableByRobots()
            ->preconnect('https://fonts.bunny.net')
            ->preconnect('https://fonts.gstatic.com')
        );
    }
}
