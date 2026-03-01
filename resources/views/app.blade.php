{{--
    Laravel app: open this page via your PHP server (APP_URL), e.g. http://localhost:8000
    Do NOT open the Vite dev server URL (e.g. http://localhost:5173) to view the app — that shows the asset server page only.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        @if (config('app.debug'))
            <div class="fixed bottom-0 right-0 z-50 rounded-tl bg-slate-800 px-2 py-1 text-xs text-slate-300" aria-hidden="true">
                App: {{ config('app.url') }} · Vite is for assets only
            </div>
        @endif
    </body>
</html>
