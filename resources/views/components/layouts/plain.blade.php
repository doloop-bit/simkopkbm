<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-slate-950 font-sans antialiased text-slate-100 selection:bg-emerald-500/30">
    <div class="relative min-h-screen flex items-center justify-center p-6 overflow-hidden">
        {{-- Animated Background Elements --}}
        <div class="absolute top-0 left-0 w-full h-full -z-10">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-emerald-500/10 rounded-full blur-[120px] animate-pulse"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-500/10 rounded-full blur-[120px] animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-[20%] right-[10%] w-[30%] h-[30%] bg-purple-500/10 rounded-full blur-[120px] animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        <div class="w-full max-w-7xl mx-auto">
            {{ $slot }}
        </div>
    </div>

    <x-ui.toast />
</body>
</html>
