<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <script>
            // Mavzuni sahifa chizilishidan oldin qo'yamiz, oq "miltillash" bo'lmasin.
            (function () {
                var theme = 'system';
                try { theme = localStorage.getItem('theme') || 'system'; } catch (e) {}
                var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>
        <title inertia>{{ config('app.name', 'e-notebook') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <script src="https://telegram.org/js/telegram-web-app.js"></script>
        @routes
        @viteReactRefresh
        @vite('resources/js/app.jsx')
        @inertiaHead
    </head>
    <body class="bg-bg text-ink font-sans antialiased">
        @inertia
    </body>
</html>
