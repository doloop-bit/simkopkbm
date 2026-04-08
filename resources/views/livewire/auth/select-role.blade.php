<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-4xl space-y-8">
        {{-- Header Section --}}
        <div class="text-center space-y-2 animate-in fade-in slide-in-from-top-4 duration-700">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white drop-shadow-md">
                {{ __('Pilih Akses Anda') }}
            </h1>
            <p class="text-lg text-emerald-100/80 font-medium">
                {{ __('Selamat datang kembali, :name. Silakan pilih role untuk melanjutkan.', ['name' => auth()->user()->name]) }}
            </p>
        </div>

        {{-- Role Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($roles as $role)
                <button
                    wire:click="selectRole({{ $role->id }})"
                    class="group relative h-full text-left transition-all duration-300 hover:-translate-y-2"
                >
                    {{-- Card Background with Glassmorphism --}}
                    <div class="h-full p-8 rounded-3xl bg-white/10 dark:bg-slate-900/40 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-2xl overflow-hidden group-hover:bg-white/20 dark:group-hover:bg-slate-900/60 group-hover:border-emerald-500/50 transition-all duration-300">
                        {{-- Decorative background gradient on hover --}}
                        <div class="absolute -right-12 -top-12 w-32 h-32 bg-emerald-500/20 rounded-full blur-3xl group-hover:bg-emerald-400/40 transition-all duration-500"></div>

                        <div class="relative z-10 space-y-6">
                            {{-- Icon --}}
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                                @php
                                    $icon = match($role->slug) {
                                        'admin' => 'o-shield-check',
                                        'bendahara' => 'o-banknotes',
                                        'guru' => 'o-academic-cap',
                                        'kepsek' => 'o-briefcase',
                                        'yayasan' => 'o-building-office-2',
                                        default => 'o-user'
                                    };
                                @endphp
                                <x-ui.icon name="{{ $icon }}" class="w-8 h-8" />
                            </div>

                            {{-- Content --}}
                            <div class="space-y-2">
                                <h3 class="text-2xl font-bold text-white group-hover:text-emerald-400 transition-colors">
                                    {{ $role->name }}
                                </h3>
                                <p class="text-slate-200/70 leading-relaxed text-sm">
                                    {{ $role->description ?? __('Akses dashboard sebagai :role', ['role' => $role->name]) }}
                                </p>
                            </div>

                            {{-- Action Label --}}
                            <div class="pt-4 flex items-center gap-2 text-emerald-400 font-bold text-sm tracking-widest uppercase">
                                <span>{{ __('Pilih Akses') }}</span>
                                <x-ui.icon name="o-arrow-small-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="text-center pt-8">
            <button
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                class="text-emerald-200/60 hover:text-white transition-colors text-sm font-medium underline underline-offset-4"
            >
                {{ __('Keluar dari akun') }}
            </button>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</div>
