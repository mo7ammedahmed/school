<?php

declare(strict_types=1);

use App\Http\Middleware\ApplySiteMetadata;
use App\Http\Middleware\EnsureSchoolContext;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PinPublicInvoiceTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Both read the session, so they must run after StartSession; the
            // locale decides which language the page titles are written in.
            SetLocale::class,
            HandleInertiaRequests::class,
            SecurityHeaders::class,
            ApplySiteMetadata::class,
        ]);

        // A payment gateway cannot hold a CSRF token. A delivery is
        // authenticated by the school's own webhook secret instead; the path is
        // rate limited in routes/web.php.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Keep a copy of the password hash in the session, so a password change
        // signs out every other device still carrying the old one.
        $middleware->authenticateSessions();

        $middleware->alias([
            'school.context' => EnsureSchoolContext::class,
            'public.invoice.tenant' => PinPublicInvoiceTenant::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
