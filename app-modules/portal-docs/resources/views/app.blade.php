<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-portal-docs>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Applied before first paint so a dark-mode reader never sees a white flash. --}}
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('portal-docs-theme');
                const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (stored === null && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (error) {
                // Private windows can throw on localStorage; the light theme is a fine fallback.
            }
        })();
    </script>
    @viteReactRefresh
    @vite([
        'app-modules/portal-docs/resources/css/portal-docs.css',
        'app-modules/portal-docs/resources/js/portal-docs.jsx',
    ])
    @inertiaHead
</head>
<body class="min-h-screen bg-white text-stone-800 antialiased dark:bg-background-dark dark:text-stone-200">
    @inertia
</body>
</html>
