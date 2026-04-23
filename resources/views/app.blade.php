<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <title inertia>{{ config('app.name') }}</title>
        
        <!-- Fonts -->
        <link rel="preload" as="style" href="{{ asset('fonts/fonts.css') }}" />
        <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}" media="print" onload="this.media='all'" />

        @inertiaHead
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (function() {
                const isAdmin = window.location.pathname.startsWith('/admin') || 
                               window.location.pathname.startsWith('/teacher') ||
                               window.location.pathname.startsWith('/select-role') ||
                               window.location.pathname.startsWith('/dev/tools');

                if (!isAdmin) {
                    document.documentElement.classList.remove('dark');
                    return;
                }

                const theme = localStorage.getItem('theme') || 'system';
                if (theme === 'system') {
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } else if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased h-full">
        @inertia
    </body>
</html>
