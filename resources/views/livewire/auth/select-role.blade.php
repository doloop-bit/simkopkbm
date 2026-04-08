<div class="space-y-12 py-8">
    {{-- Header Section --}}
    <div class="text-center space-y-4 animate-in fade-in slide-in-from-top-4 duration-1000">
        <div class="flex justify-center mb-6">
            <x-global.app-logo-icon class="w-16 h-16 fill-emerald-500 drop-shadow-[0_0_15px_rgba(16,185,129,0.3)]" />
        </div>
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-white drop-shadow-2xl">
            {{ __('Pilih Akses Anda') }}
        </h1>
        <p class="text-xl text-slate-400 font-medium max-w-2xl mx-auto">
            {{ __('Selamat datang kembali, :name. Silakan pilih role untuk melanjutkan akses ke sistem.', ['name' => auth()->user()->name]) }}
        </p>
    </div>

    {{-- Role Cards Grid --}}
    <div class="flex flex-wrap justify-center gap-8 max-w-7xl mx-auto px-4">
        @foreach($roles as $role)
            <button
                wire:click="selectRole({{ $role->id }})"
                class="group relative h-full text-left transition-all duration-500 hover:-translate-y-3 w-full sm:w-[380px]"
            >
                {{-- Card Background with Glassmorphism --}}
                <div class="h-full p-8 rounded-[2.5rem] bg-white/5 backdrop-blur-2xl border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden group-hover:bg-white/10 group-hover:border-emerald-500/50 transition-all duration-500 flex flex-col justify-between min-h-[320px]">
                    {{-- Animated background gradient on hover --}}
                    <div class="absolute -right-16 -top-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-[80px] group-hover:bg-emerald-500/30 group-hover:scale-150 transition-all duration-700"></div>
                    
                    <div class="relative z-10 space-y-8">
                        {{-- Icon Container --}}
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white shadow-[0_10px_20px_rgba(16,185,129,0.4)] group-hover:scale-110 group-hover:rotate-3 transition-all duration-500">
                            @php
                                $icon = match($role->slug) {
                                    'admin' => 'o-shield-check',
                                    'bendahara' => 'o-banknotes',
                                    'guru' => 'o-academic-cap',
                                    'kepsek' => 'o-briefcase',
                                    'yayasan' => 'o-building-office-2',
                                    'siswa' => 'o-user-group',
                                    default => 'o-user'
                                };
                            @endphp
                            <x-ui.icon name="{{ $icon }}" class="w-10 h-10" />
                        </div>

                        {{-- Content --}}
                        <div class="space-y-4">
                            <h3 class="text-3xl font-extrabold text-white group-hover:text-emerald-400 transition-colors tracking-tight">
                                {{ $role->name }}
                            </h3>
                            <p class="text-slate-400 leading-relaxed text-base group-hover:text-slate-300 transition-colors">
                                {{ $role->description ?? __('Kelola data dan akses fitur sebagai :role.', ['role' => $role->name]) }}
                            </p>
                        </div>
                    </div>

                    {{-- Action Label --}}
                    <div class="relative z-10 pt-6 flex items-center gap-3 text-emerald-400 font-black text-sm tracking-[0.2em] uppercase transition-all duration-300 group-hover:gap-5">
                        <span>{{ __('Pilih Akses') }}</span>
                        <x-ui.icon name="o-arrow-right" class="w-5 h-5 group-hover:translate-x-2 transition-transform" />
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="text-center pt-12 animate-in fade-in duration-1000 delay-500">
        <button
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="group inline-flex items-center gap-2 text-slate-500 hover:text-white transition-all duration-300 text-sm font-bold uppercase tracking-widest"
        >
            <x-ui.icon name="o-arrow-left-on-rectangle" class="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
            <span class="border-b border-transparent group-hover:border-white transition-all">{{ __('Log Out & Kembali') }}</span>
        </button>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
