@props([
    'title' => config('app.name'),
    'programName' => null,
    'programLogo' => null,
    'entryPoint' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

        <script>
            window.programName = @json($programName);
            window.programLogo = @json($programLogo);
        </script>

        @if($entryPoint)
            @vite(['resources/css/app.css', $entryPoint])
        @else
            @vite(['resources/css/app.css'])
        @endif

        @stack('heads')
    </head>
    <body class="antialiased">
        {{ $slot }}
        @stack('scripts')
    </body>
</html>
