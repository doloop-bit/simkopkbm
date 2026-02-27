<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<meta name="description" content="{{ config('app.name') }} adalah Sistem Informasi Manajemen Koperasi dan PKBM yang komprehensif untuk pengelolaan data siswa, penilaian, dan keuangan." />
<meta name="robots" content="{{ $robots ?? 'noindex, nofollow' }}" />


<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<!-- Fonts Optimization (Self-hosted) -->
<link rel="preload" as="style" href="{{ asset('fonts/fonts.css') }}" />
<link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}" media="print" onload="this.media='all'" />


@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    (function() {
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

