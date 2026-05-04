<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>API Docs - {{ config('app.name', 'Support Desk') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script>
            (() => {
                const key = 'supportdesk-theme';
                const stored = localStorage.getItem(key);
                const preference = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const dark = preference === 'dark' || (preference === 'system' && systemDark);

                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>

        @vite('resources/js/swagger.ts')
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="border-b bg-card/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Support Desk API</p>
                    <h1 class="mt-1 text-xl font-semibold tracking-normal">Interactive API documentation</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Signed in as {{ $userName }}</p>
                </div>
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex h-9 items-center justify-center rounded-md border bg-background px-3 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-secondary"
                >
                    Back to app
                </a>
            </div>
        </div>

        <main class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-md border bg-card shadow-sm">
                <div id="swagger-ui" data-spec-url="{{ $specUrl }}"></div>
            </div>
        </main>
    </body>
</html>
