<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Session;

new #[Layout('components.layouts.plain')] class extends Component {
    public function with(): array
    {
        return [
            'roles' => auth()->user()->roles,
        ];
    }

    public function selectRole(int $roleId): void
    {
        $user = auth()->user();
        $role = $user->roles()->find($roleId);

        if ($role) {
            Session::put('active_role_id', $role->id);

            // Redirect based on role slug
            $redirectUrl = match ($role->slug) {
                'guru' => route('teacher.dashboard'),
                'siswa' => route('home'),
                default => route('dashboard'),
            };

            $this->redirect($redirectUrl, navigate: true);
        }
    }
};

?>

<div class="space-y-4 md:space-y-12 py-4 md:py-8 px-4 h-full flex flex-col justify-center">
    {{-- Header Section --}}
    <div class="text-center space-y-2 md:space-y-4 animate-in fade-in slide-in-from-top-4 duration-1000">
        <div class="flex justify-center mb-2 md:mb-6">
            <x-global.app-logo-icon class="size-10 md:size-16 shrink-0 fill-emerald-500 drop-shadow-[0_0_15px_rgba(16,185,129,0.3)]" />
        </div>
        <h1 class="text-2xl md:text-6xl font-black tracking-tight text-white drop-shadow-2xl">
            {{ __('Pilih Akses') }}
        </h1>
        <p class="text-sm md:text-xl text-slate-400 font-medium max-w-2xl mx-auto line-clamp-1 md:line-clamp-none">
            {{ __('Pilih role untuk melanjutkan akses ke sistem.') }}
        </p>
    </div>

    {{-- Role Cards Grid --}}
    <div class="grid grid-cols-2 md:flex md:flex-wrap md:justify-center gap-3 md:gap-8 max-w-6xl mx-auto w-full px-2 md:px-0">
        @foreach($roles as $role)
            <button
                wire:click="selectRole({{ $role->id }})"
                class="group relative text-left transition-all duration-500 hover:-translate-y-2 shrink-0 md:w-[320px]"
            >
                {{-- Card Background with Glassmorphism --}}
                <div class="h-full p-4 md:p-8 rounded-2xl md:rounded-[2.5rem] bg-white/5 backdrop-blur-2xl border border-white/10 shadow-xl overflow-hidden group-hover:bg-white/10 group-hover:border-emerald-500/50 transition-all duration-500 flex flex-col justify-between min-h-[140px] md:min-h-[320px]">
                    {{-- Animated background gradient on hover --}}
                    <div class="absolute -right-8 -top-8 md:-right-16 md:-top-16 size-24 md:size-48 bg-emerald-500/10 rounded-full blur-[40px] md:blur-[80px] group-hover:bg-emerald-500/30 group-hover:scale-150 transition-all duration-700"></div>
                    
                    <div class="relative z-10 space-y-3 md:space-y-8">
                        {{-- Icon Container --}}
                        <div class="size-10 md:size-20 shrink-0 aspect-square rounded-xl md:rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 mx-0">
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
                            <x-ui.icon name="{{ $icon }}" class="size-5 md:size-10 shrink-0" />
                        </div>

                        {{-- Content --}}
                        <div class="space-y-1 md:space-y-4">
                            <h3 class="text-lg md:text-3xl font-extrabold text-white group-hover:text-emerald-400 transition-colors tracking-tight">
                                {{ $role->name }}
                            </h3>
                            <p class="hidden md:block text-slate-400 leading-relaxed text-base group-hover:text-slate-300 transition-colors">
                                {{ $role->description ?? __('Kelola data dan akses fitur sebagai :role.', ['role' => $role->name]) }}
                            </p>
                        </div>
                    </div>

                    {{-- Action Label --}}
                    <div class="relative z-10 pt-2 md:pt-6 flex items-center gap-2 md:gap-3 text-emerald-400 font-black text-[10px] md:text-sm tracking-[0.1em] md:tracking-[0.2em] uppercase transition-all duration-300">
                        <span class="md:hidden">PILIH</span>
                        <span class="hidden md:block">{{ __('Pilih Akses') }}</span>
                        <x-ui.icon name="o-arrow-right" class="w-3 h-3 md:w-5 h-5 group-hover:translate-x-1 transition-transform" />
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="text-center pt-4 md:pt-12 animate-in fade-in duration-1000 delay-500">
        <button
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="group inline-flex items-center gap-2 text-slate-500 hover:text-white transition-all duration-300 text-[10px] md:text-sm font-bold uppercase tracking-widest"
        >
            <x-ui.icon name="o-arrow-left-on-rectangle" class="w-4 h-4 md:w-5 h-5 group-hover:-translate-x-1 transition-transform" />
            <span class="border-b border-transparent group-hover:border-white transition-all">{{ __('Log Out & Kembali') }}</span>
        </button>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
