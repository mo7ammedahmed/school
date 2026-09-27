<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{--
        Laravel Head resolves the 404 metadata registered in AppServiceProvider
        (a title plus noindex, nofollow); this view only has to render it. The
        styling is inline because an error page must not depend on a built
        asset manifest.
    --}}
    @head
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif;
            background: #faf9f5;
            color: #1c1a16;
            text-align: center;
        }
        .code { font-size: 3.5rem; font-weight: 600; color: #0a5c42; margin: 0; }
        h1 { font-size: 1.4rem; margin: 0.5rem 0 0; }
        p { color: #74705f; margin: 0.5rem 0 1.25rem; }
        a {
            display: inline-block;
            padding: 0.55rem 1.1rem;
            border-radius: 0.6rem;
            background: #0a5c42;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #070707; color: #f4f4f1; }
            p { color: #a4a4a8; }
        }
    </style>
</head>
<body>
    <main>
        <p class="code">404</p>
        <h1>{{ __('Page Not Found') }}</h1>
        <p>{{ __('The page you are looking for has moved or never existed.') }}</p>
        <a href="{{ url('/') }}">{{ __('Back to the homepage') }}</a>
    </main>
</body>
</html>
