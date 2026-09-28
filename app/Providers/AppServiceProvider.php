<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Localization\Observers\FillsMissingTranslations;
use App\Domain\Localization\Services\ArabicShaper;
use App\Http\Middleware\ApplySiteMetadata;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

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

        $this->registerHeadDefaults();
        $this->registerErrorMetadata();
        $this->registerArabicShaper();

        FillsMissingTranslations::register();
    }

    /**
     * dompdf cannot join Arabic letters, so PDF views hand their text through
     * {@see ArabicShaper} with `@shaped($value)`.
     */
    private function registerArabicShaper(): void
    {
        Blade::directive('shaped', fn (string $expression): string => "<?php echo e(\\App\\Domain\\Localization\\Services\\ArabicShaper::forLocale({$expression})); ?>");
    }

    /**
     * The floor under every page's metadata. These values are only used when
     * nothing more specific exists: the school's own identity is applied per
     * request by {@see ApplySiteMetadata}.
     */
    private function registerHeadDefaults(): void
    {
        Head::defaults(fn (HeadBuilder $head) => $head
            // No suffix here: the school's name is the suffix, and it is only
            // known once the request has been resolved.
            ->title(config('app.name'))
            ->description('School management for admissions, academics, attendance and finance.')
            ->canonical()
            ->colorScheme('light dark')
            ->referrer('strict-origin-when-cross-origin')
            ->searchableByRobots()
            ->preconnect('https://fonts.bunny.net')
        );

    }

    /**
     * Error screens must never be indexed, and should still name themselves.
     */
    private function registerErrorMetadata(): void
    {
        Head::errors(function (ErrorPages $errors): void {
            $errors->defaults(robots: 'noindex, nofollow');

            $errors->status(403, title: 'Not Allowed');
            $errors->status(404, title: 'Page Not Found', description: 'The page you are looking for has moved or never existed.');
            $errors->status(419, title: 'Session Expired', description: 'Please reload the page and try again.');
            $errors->status(500, title: 'Something Went Wrong');
            $errors->status(503, title: 'Temporarily Unavailable');
        });
    }
}
