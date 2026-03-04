<?php

declare(strict_types=1);

use App\Models\{Registration, Level, AcademicYear};
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

new #[Layout('components.public.layouts.public')] class extends Component {
    use WithFileUploads;

    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Step 1: Data Pribadi
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public string $nik = '';

    #[Validate('nullable|string')]
    public string $pob = '';

    #[Validate('nullable|date')]
    public ?string $dob = null;

    #[Validate('nullable|in:L,P')]
    public string $gender = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    // Step 2: Alamat
    #[Validate('nullable|string|max:500')]
    public string $address = '';

    public string $province_id = '';
    public string $province_name = '';
    public string $regency_id = '';
    public string $regency_name = '';
    public string $district_id = '';
    public string $district_name = '';
    public string $village_id = '';
    public string $village_name = '';

    // Step 3: Data Orang Tua/Wali
    #[Validate('nullable|string|max:255')]
    public string $father_name = '';

    #[Validate('nullable|string|max:255')]
    public string $mother_name = '';

    #[Validate('nullable|string|max:255')]
    public string $guardian_name = '';

    #[Validate('nullable|string|max:20')]
    public string $guardian_phone = '';

    #[Validate('nullable|string|max:20')]
    public string $nik_ayah = '';

    #[Validate('nullable|string|max:20')]
    public string $nik_ibu = '';

    #[Validate('nullable|string|max:20')]
    public string $no_kk = '';

    #[Validate('nullable|string|max:20')]
    public string $no_akta = '';

    #[Validate('nullable|integer|min:1')]
    public ?int $birth_order = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $total_siblings = null;

    // Step 4: Data Akademik
    #[Validate('nullable|string|max:20')]
    public string $nisn = '';

    #[Validate('nullable|string|max:255')]
    public string $previous_school = '';

    #[Validate('nullable|exists:levels,id')]
    public ?int $preferred_level_id = null;

    #[Validate('nullable|exists:academic_years,id')]
    public ?int $academic_year_id = null;

    // Result
    public bool $submitted = false;
    public string $registrationNumber = '';

    // Honeypot
    public string $extra_field = '';

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'name' => 'required|string|max:255',
            ]);
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    public function submit(Request $request): void
    {
        // 1. Honeypot check
        if (!empty($this->extra_field)) {
            abort(422, 'Spam detected.');
        }

        // 2. Rate limiting (max 3 submissions per 30 minutes per IP)
        $key = 'registration-submit:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('submit', "Terlalu banyak percobaan. Harap tunggu {$seconds} detik.");
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'nik' => ['nullable', 'string', 'max:16', function ($attribute, $value, $fail) {
                if ($value && Registration::where('nik', $value)->where('status', 'pending')->exists()) {
                    $fail('NIK ini sudah memiliki pendaftaran yang sedang diproses.');
                }
            }],
            'nisn' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if ($value && Registration::where('nisn', $value)->where('status', 'pending')->exists()) {
                    $fail('NISN ini sudah memiliki pendaftaran yang sedang diproses.');
                }
            }],
        ]);

        RateLimiter::hit($key, 1800); // 30 minutes expiry

        $registration = Registration::create([
            'registration_number' => Registration::generateRegistrationNumber(),
            'name' => $this->name,
            'nik' => $this->nik ?: null,
            'nisn' => $this->nisn ?: null,
            'pob' => $this->pob ?: null,
            'dob' => $this->dob ?: null,
            'gender' => $this->gender ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'province_id' => $this->province_id ?: null,
            'province_name' => $this->province_name ?: null,
            'regency_id' => $this->regency_id ?: null,
            'regency_name' => $this->regency_name ?: null,
            'district_id' => $this->district_id ?: null,
            'district_name' => $this->district_name ?: null,
            'village_id' => $this->village_id ?: null,
            'village_name' => $this->village_name ?: null,
            'father_name' => $this->father_name ?: null,
            'mother_name' => $this->mother_name ?: null,
            'guardian_name' => $this->guardian_name ?: null,
            'guardian_phone' => $this->guardian_phone ?: null,
            'nik_ayah' => $this->nik_ayah ?: null,
            'nik_ibu' => $this->nik_ibu ?: null,
            'no_kk' => $this->no_kk ?: null,
            'no_akta' => $this->no_akta ?: null,
            'birth_order' => $this->birth_order,
            'total_siblings' => $this->total_siblings,
            'previous_school' => $this->previous_school ?: null,
            'preferred_level_id' => $this->preferred_level_id,
            'academic_year_id' => $this->academic_year_id,
            'status' => 'pending',
        ]);

        $this->registrationNumber = $registration->registration_number;
        $this->submitted = true;
    }

    public function with(): array
    {
        return [
            'levels' => Level::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_year')->get(),
            'wilayahApiUrl' => config('services.wilayah.api_url', 'https://emsifa.github.io/api-wilayah-indonesia/api'),
            'title' => 'Pendaftaran - ' . config('app.name'),
            'description' => 'Daftar sebagai siswa baru di ' . config('app.name'),
        ];
    }
}; ?>

<div>
    <!-- Dynamic High-End Page Header -->
    <div class="relative bg-slate-900 text-white overflow-hidden py-24 sm:py-32">
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none" preserveAspectRatio="none">
                <defs>
                    <pattern id="reg-grid-pattern" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.2"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#reg-grid-pattern)" />
            </svg>
        </div>
        
        <div class="absolute -top-24 -left-20 w-96 h-96 bg-primary/20 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-24 -right-20 w-96 h-96 bg-amber-500/10 blur-[120px] rounded-full"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center space-y-6">
            <x-ui.badge :label="__('Penerimaan Siswa Baru')" class="bg-primary/20 text-primary-foreground border-none font-black italic text-[10px] px-4 py-1.5 uppercase tracking-[0.2em]" />
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-black font-heading tracking-tighter italic uppercase leading-none">
                Formulir <span class="text-primary truncate">Registrasi</span>
            </h1>
            <p class="text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto font-medium leading-relaxed">
                {{ __('Silakan lengkapi seluruh informasi yang dibutuhkan untuk memulai perjalanan akademik Anda bersama kami.') }}
            </p>
            <div class="w-24 h-1.5 bg-primary mx-auto rounded-full shadow-lg shadow-primary/20"></div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        @if($submitted)
            <!-- Premium Success State -->
            <div class="text-center py-20 animate-in zoom-in duration-700">
                <div class="w-24 h-24 bg-emerald-50 dark:bg-emerald-950 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-emerald-500/20 rotate-3 group hover:rotate-0 transition-transform duration-500">
                    <x-ui.icon name="o-check-circle" class="size-12 text-emerald-500" />
                </div>
                
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic mb-4">
                    {{ __('Pendaftaran Terkirim!') }}
                </h2>
                
                <div class="max-w-md mx-auto p-8 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 mb-10">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">{{ __('ID REGISTRASI ANDA') }}</p>
                    <div class="text-4xl font-black text-primary font-mono tracking-tighter drop-shadow-sm mb-4">
                        {{ $registrationNumber }}
                    </div>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        {{ __('Simpan nomor pendaftaran ini sebagai bukti registrasi. Tim administrasi kami akan segera meninjau data Anda.') }}
                    </p>
                </div>

                <x-ui.button 
                    href="{{ route('home') }}" 
                    :label="__('Kembali ke Beranda')" 
                    icon="o-arrow-left" 
                    class="btn-primary px-8 py-3 rounded-2xl shadow-xl shadow-primary/20 font-black italic tracking-tighter uppercase" 
                    wire:navigate 
                />
            </div>
        @else
            <!-- High-End Step Indicators -->
            <div class="mb-12">
                <div class="flex items-center justify-between relative px-2">
                    <!-- Dynamic Progress Timeline -->
                    <div class="absolute top-5 left-8 right-8 h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                        <div class="h-full bg-primary transition-all duration-1000 ease-out shadow-[0_0_15px_rgba(var(--color-primary),0.4)]"
                             style="width: {{ ($currentStep - 1) / ($totalSteps - 1) * 100 }}%"></div>
                    </div>
 
                    @php
                        $steps = [
                            ['label' => __('Personalisasi'), 'icon' => 'o-user'],
                            ['label' => __('Domisili'), 'icon' => 'o-map-pin'],
                            ['label' => __('Famili'), 'icon' => 'o-users'],
                            ['label' => __('Akademik'), 'icon' => 'o-academic-cap'],
                        ];
                    @endphp
 
                    @foreach($steps as $index => $step)
                        <button 
                            type="button"
                            wire:click="goToStep({{ $index + 1 }})"
                            @disabled($index + 1 > $currentStep)
                            class="relative z-10 flex flex-col items-center group transition-all duration-500"
                        >
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 shadow-sm
                                {{ $index + 1 < $currentStep ? 'bg-primary text-primary-foreground scale-90' : '' }}
                                {{ $index + 1 === $currentStep ? 'bg-white dark:bg-slate-900 text-primary ring-4 ring-primary/10 shadow-xl shadow-primary/10 -translate-y-1' : '' }}
                                {{ $index + 1 > $currentStep ? 'bg-slate-50 dark:bg-slate-900 text-slate-300' : '' }}">
                                
                                <x-ui.icon 
                                    :name="$index + 1 < $currentStep ? 'o-check' : $step['icon']" 
                                    class="size-5 transition-transform duration-500 group-hover:scale-110" 
                                />
                            </div>
                            
                            <div class="absolute top-16 whitespace-nowrap hidden sm:block">
                                <span class="text-[10px] font-black italic uppercase tracking-widest transition-colors duration-500
                                    {{ $index + 1 <= $currentStep ? 'text-primary' : 'text-slate-400' }}">
                                    {{ $step['label'] }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Premium Form Container -->
            <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-100 dark:ring-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-2xl">
                <div class="p-8 sm:p-12">
                    <form wire:submit="submit">
                        {{-- Honeypot field --}}
                        <div class="hidden" aria-hidden="true">
                            <input type="text" wire:model="extra_field" tabindex="-1" autocomplete="off">
                        </div>
 
                        @error('submit')
                            <x-ui.alert :title="__('Gangguan Registrasi')" icon="o-exclamation-triangle" class="mb-8 bg-rose-50 text-rose-800 border-rose-100 shadow-lg shadow-rose-500/5">
                                {{ $message }}
                            </x-ui.alert>
                        @enderror

                        <!-- Step 1: Personal Profile -->
                        @if ($currentStep === 1)
                        <div wire:key="step-1" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter italic leading-none">{{ __('Profil Personal Calon Siswa') }}</h2>
                                <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Informasi fundamental identitas diri') }}</p>
                            </div>
 
                            <div class="space-y-6">
                                <x-ui.input 
                                    wire:model="name"
                                    :label="__('Nama Lengkap (Sesuai Akta Lahir)')"
                                    :placeholder="__('Masukkan nama lengkap Anda...')"
                                    class="font-black italic uppercase tracking-tighter"
                                    required
                                />
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="nik" 
                                        maxlength="16"
                                        :label="__('Nomor Induk Kependudukan (NIK)')"
                                        :placeholder="__('16 digit NIK')"
                                        class="font-mono tracking-widest"
                                    />
 
                                    <x-ui.select 
                                        wire:model="gender"
                                        :label="__('Klasifikasi Gender')"
                                        :options="[['id' => 'L', 'name' => 'Laki-laki'], ['id' => 'P', 'name' => 'Perempuan']]"
                                        class="font-bold italic uppercase tracking-tighter"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div x-data="pobHandler('{{ $wilayahApiUrl }}')" x-init="init()">
                                        <x-ui.input 
                                            wire:model="pob"
                                            x-ref="pobInput"
                                            x-on:focus="onFocus()"
                                            x-on:input="onInput($event)"
                                            list="pob-suggestions"
                                            :label="__('Tempat Kelahiran')"
                                            :placeholder="__('KOTA/KABUPATEN KELAHIRAN')"
                                            class="font-bold italic uppercase tracking-tighter"
                                        />
                                        <datalist id="pob-suggestions" class="hidden">
                                            <template x-for="suggestion in suggestions" :key="suggestion">
                                                <option :value="suggestion"></option>
                                            </template>
                                        </datalist>
                                    </div>
 
                                    <x-ui.input 
                                        wire:model="dob" 
                                        type="date" 
                                        :label="__('Tanggal Kelahiran')" 
                                        class="font-mono"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        type="tel" 
                                        wire:model="phone"
                                        :label="__('Nomor Kontak / WhatsApp')"
                                        :placeholder="__('08xxxxxxxxxx')"
                                        icon="o-phone"
                                        class="font-mono"
                                    />
 
                                    <x-ui.input 
                                        type="email" 
                                        wire:model="email"
                                        :label="__('Alamat Surel (Email)')"
                                        :placeholder="__('email@identitas.com')"
                                        icon="o-envelope"
                                        class="font-medium"
                                    />
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 2: Domicile Details -->
                        @if ($currentStep === 2)
                        <div wire:key="step-2" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700"
                             x-data="addressPicker('{{ $wilayahApiUrl }}')"
                             x-init="init()">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter italic leading-none">{{ __('Data Domisili & Wilayah') }}</h2>
                                <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Lokasi tempat tinggal calon siswa saat ini') }}</p>
                            </div>
 
                            <div class="space-y-6">
                                <x-ui.textarea 
                                    wire:model="address" 
                                    :label="__('Alamat Lengkap (Jl, No Rumah, RT/RW)')"
                                    :placeholder="__('Tuliskan alamat lengkap...')"
                                    rows="3"
                                    class="font-bold italic uppercase tracking-tighter"
                                />
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ __('Provinsi') }}</label>
                                        <select x-model="selectedProvince" @change="onProvinceChange()"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-black italic uppercase tracking-tighter text-sm focus:ring-primary transition-all appearance-none outline-none">
                                            <option value="">{{ __('Pilih Provinsi...') }}</option>
                                            <template x-for="prov in provinces" :key="prov.id">
                                                <option :value="prov.id + '|' + prov.name" x-text="prov.name"></option>
                                            </template>
                                        </select>
                                    </div>
 
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ __('Kabupaten / Kota') }}</label>
                                        <select x-model="selectedRegency" @change="onRegencyChange()"
                                            :disabled="regencies.length === 0"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-black italic uppercase tracking-tighter text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kabupaten/Kota...') }}</option>
                                            <template x-for="reg in regencies" :key="reg.id">
                                                <option :value="reg.id + '|' + reg.name" x-text="reg.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ __('Kecamatan') }}</label>
                                        <select x-model="selectedDistrict" @change="onDistrictChange()"
                                            :disabled="districts.length === 0"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-black italic uppercase tracking-tighter text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kecamatan...') }}</option>
                                            <template x-for="dist in districts" :key="dist.id">
                                                <option :value="dist.id + '|' + dist.name" x-text="dist.name"></option>
                                            </template>
                                        </select>
                                    </div>
 
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ __('Kelurahan / Desa') }}</label>
                                        <select x-model="selectedVillage" @change="onVillageChange()"
                                            :disabled="villages.length === 0"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-black italic uppercase tracking-tighter text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kelurahan/Desa...') }}</option>
                                            <template x-for="vil in villages" :key="vil.id">
                                                <option :value="vil.id + '|' + vil.name" x-text="vil.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
 
                                <!-- Sophisticated Loading State -->
                                <div x-show="loading" class="flex items-center gap-3 bg-primary/5 p-4 rounded-2xl border border-primary/10 animate-pulse">
                                    <x-ui.icon name="o-arrow-path" class="size-4 text-primary animate-spin" />
                                    <span class="text-[10px] font-black text-primary uppercase tracking-widest">{{ __('Sinkronisasi Data Wilayah...') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 3: Guardian & Family Dynamic -->
                        @if ($currentStep === 3)
                        <div wire:key="step-3" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter italic leading-none">{{ __('Data Orang Tua / Wali') }}</h2>
                                <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Informasi penanggung jawab & asal usul keluarga') }}</p>
                            </div>
 
                            <div class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="father_name"
                                        :label="__('Nama Lengkap Ayah Kandung')"
                                        :placeholder="__('Nama sesuai dokumen resmi')"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                    <x-ui.input 
                                        wire:model="nik_ayah" 
                                        maxlength="16"
                                        :label="__('NIK Ayah')"
                                        :placeholder="__('16 digit NIK')"
                                        class="font-mono"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="mother_name"
                                        :label="__('Nama Lengkap Ibu Kandung')"
                                        :placeholder="__('Nama sesuai dokumen resmi')"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                    <x-ui.input 
                                        wire:model="nik_ibu" 
                                        maxlength="16"
                                        :label="__('NIK Ibu')"
                                        :placeholder="__('16 digit NIK')"
                                        class="font-mono"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="no_kk" 
                                        maxlength="16"
                                        :label="__('Nomor Kartu Keluarga (KK)')"
                                        :placeholder="__('16 digit nomor KK')"
                                        class="font-mono"
                                    />
                                    <x-ui.input 
                                        wire:model="no_akta"
                                        :label="__('Nomor Registrasi Akta Lahir')"
                                        :placeholder="__('Sesuai yang tertera di akta')"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                </div>
 
                                <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700 space-y-6">
                                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Opsi Penanggung Jawab Lain / Wali') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <x-ui.input 
                                            wire:model="guardian_name"
                                            :label="__('Nama Lengkap Wali')"
                                            :placeholder="__('Opsional jika ada wali')"
                                            class="font-black italic uppercase tracking-tighter"
                                        />
                                        <x-ui.input 
                                            wire:model="guardian_phone"
                                            :label="__('Kontak Aktif Wali')"
                                            :placeholder="__('08xxxxxxxxxx')"
                                            class="font-mono"
                                        />
                                    </div>
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 dark:border-slate-800 pt-8">
                                    <x-ui.input 
                                        type="number" 
                                        wire:model="birth_order" 
                                        min="1"
                                        :label="__('Urutan Kelahiran (Anak Ke-)')"
                                        class="font-black italic"
                                    />
                                    <x-ui.input 
                                        type="number" 
                                        wire:model="total_siblings" 
                                        min="0"
                                        :label="__('Jumlah Saudara Kandung')"
                                        class="font-black italic"
                                    />
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 4: Academic Foundation & Review -->
                        @if ($currentStep === 4)
                        <div wire:key="step-4" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tighter italic leading-none">{{ __('Data Akademik & Preferensi') }}</h2>
                                <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Tahap akhir verifikasi informasi akademik') }}</p>
                            </div>
 
                            <div class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="nisn" 
                                        maxlength="10"
                                        :label="__('Nomor Induk Siswa Nasional (NISN)')"
                                        :placeholder="__('10 digit NISN')"
                                        class="font-mono"
                                    />
                                    <x-ui.input 
                                        wire:model="previous_school"
                                        :label="__('Lembaga Pendidikan Sebelumnya')"
                                        :placeholder="__('Nama sekolah/asal instansi')"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.select 
                                        wire:model="preferred_level_id"
                                        :label="__('Jenjang yang Diminati')"
                                        :options="$levels"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                    <x-ui.select 
                                        wire:model="academic_year_id"
                                        :label="__('Periode Tahun Ajaran')"
                                        :options="$academicYears"
                                        class="font-black italic uppercase tracking-tighter"
                                    />
                                </div>
                            </div>

                            <!-- Sophisticated Summary Preview -->
                            <div class="mt-12 p-8 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-8 opacity-5">
                                    <x-ui.icon name="o-check-badge" class="size-32" />
                                </div>
 
                                <h3 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                                    <span class="w-8 h-px bg-primary/20"></span>
                                    {{ __('Ringkasan Konfirmasi Registrasi') }}
                                </h3>
 
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-4 relative text-slate-800 dark:text-slate-200">
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Nama Lengkap') }}</span>
                                            <span class="text-xs font-black text-slate-700 dark:text-slate-300 italic uppercase tracking-tighter text-right">{{ $name ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Identitas (NIK)') }}</span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono text-right">{{ $nik ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Domisili TTL') }}</span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 italic uppercase tracking-tighter text-right">{{ $pob ?: '-' }}, {{ $dob ? \Carbon\Carbon::parse($dob)->format('d/m/Y') : '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Domisili Aktif') }}</span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 italic uppercase tracking-tighter text-right overflow-hidden text-ellipsis whitespace-nowrap max-w-[200px]">{{ $address ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Orang Tua (A/I)') }}</span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 italic uppercase tracking-tighter text-right">{{ $father_name ?: '-' }} / {{ $mother_name ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Tahun Ajaran') }}</span>
                                            @php $selectedYear = $academic_year_id ? collect($academicYears)->firstWhere('id', (int)$academic_year_id) : null; @endphp
                                            <span class="text-xs font-black text-primary italic uppercase tracking-tighter text-right">{{ $selectedYear?->name ?? ($selectedYear?->start_year . '/' . $selectedYear?->end_year ?? '-') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- High-End Navigation Controls -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-12 pt-10 border-t border-slate-100 dark:border-slate-800">
                            @if($currentStep > 1)
                                <x-ui.button 
                                    type="button" 
                                    wire:click="previousStep" 
                                    :label="__('Kembali')" 
                                    icon="o-arrow-left" 
                                    class="w-full sm:w-auto btn-ghost text-slate-400 font-black italic uppercase tracking-tighter px-8" 
                                />
                            @else
                                <div class="hidden sm:block"></div>
                            @endif
 
                            @if($currentStep < $totalSteps)
                                <x-ui.button 
                                    type="button" 
                                    wire:click="nextStep" 
                                    :label="__('Langkah Berikutnya')" 
                                    icon-right="o-arrow-right" 
                                    class="w-full sm:w-auto btn-primary shadow-xl shadow-primary/20 px-10 font-black italic uppercase tracking-tighter" 
                                />
                            @else
                                <x-ui.button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    :label="__('Submit Pendaftaran')" 
                                    icon="o-paper-airplane" 
                                    class="w-full sm:w-auto btn-primary bg-emerald-500 hover:bg-emerald-600 border-none shadow-xl shadow-emerald-500/20 px-12 font-black italic uppercase tracking-tighter py-6 h-auto" 
                                    spinner="submit"
                                />
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Alpine.js Address Picker Component -->
    <script>
        function addressPicker(apiUrl) {
            return {
                apiUrl: apiUrl,
                loading: false,
                provinces: [],
                regencies: [],
                districts: [],
                villages: [],
                selectedProvince: '',
                selectedRegency: '',
                selectedDistrict: '',
                selectedVillage: '',

                async init() {
                    const pId = this.$wire.get('province_id');
                    const rId = this.$wire.get('regency_id');
                    const dId = this.$wire.get('district_id');
                    const vId = this.$wire.get('village_id');
                    const pName = this.$wire.get('province_name');
                    const rName = this.$wire.get('regency_name');
                    const dName = this.$wire.get('district_name');
                    const vName = this.$wire.get('village_name');

                    this.loading = true;
                    await this.loadProvinces();

                    if (pId) {
                        this.selectedProvince = `${pId}|${pName}`;
                        await this.fetchRegencies(pId);
                    }
                    if (rId) {
                        this.selectedRegency = `${rId}|${rName}`;
                        await this.fetchDistricts(rId);
                    }
                    if (dId) {
                        this.selectedDistrict = `${dId}|${dName}`;
                        await this.fetchVillages(dId);
                    }
                    if (vId) {
                        this.selectedVillage = `${vId}|${vName}`;
                    }
                    this.loading = false;
                },

                async loadProvinces() {
                    try {
                        const res = await fetch(`${this.apiUrl}/provinces.json`);
                        this.provinces = await res.json();
                    } catch (e) { console.error('Error:', e); }
                },

                async fetchRegencies(id) {
                    try {
                        const res = await fetch(`${this.apiUrl}/regencies/${id}.json`);
                        this.regencies = await res.json();
                    } catch (e) { console.error('Error:', e); }
                },

                async fetchDistricts(id) {
                    try {
                        const res = await fetch(`${this.apiUrl}/districts/${id}.json`);
                        this.districts = await res.json();
                    } catch (e) { console.error('Error:', e); }
                },

                async fetchVillages(id) {
                    try {
                        const res = await fetch(`${this.apiUrl}/villages/${id}.json`);
                        this.villages = await res.json();
                    } catch (e) { console.error('Error:', e); }
                },

                async onProvinceChange() {
                    this.regencies = []; this.districts = []; this.villages = [];
                    this.selectedRegency = ''; this.selectedDistrict = ''; this.selectedVillage = '';

                    if (!this.selectedProvince) {
                        this.$wire.set('province_id', ''); this.$wire.set('province_name', '');
                        return;
                    }

                    const [id, name] = this.selectedProvince.split('|');
                    this.$wire.set('province_id', id); this.$wire.set('province_name', name);
                    this.$wire.set('regency_id', ''); this.$wire.set('regency_name', '');
                    this.$wire.set('district_id', ''); this.$wire.set('district_name', '');
                    this.$wire.set('village_id', ''); this.$wire.set('village_name', '');

                    this.loading = true;
                    await this.fetchRegencies(id);
                    this.loading = false;
                },

                async onRegencyChange() {
                    this.districts = []; this.villages = [];
                    this.selectedDistrict = ''; this.selectedVillage = '';

                    if (!this.selectedRegency) {
                        this.$wire.set('regency_id', ''); this.$wire.set('regency_name', '');
                        return;
                    }

                    const [id, name] = this.selectedRegency.split('|');
                    this.$wire.set('regency_id', id); this.$wire.set('regency_name', name);
                    this.$wire.set('district_id', ''); this.$wire.set('district_name', '');
                    this.$wire.set('village_id', ''); this.$wire.set('village_name', '');

                    this.loading = true;
                    await this.fetchDistricts(id);
                    this.loading = false;
                },

                async onDistrictChange() {
                    this.villages = []; this.selectedVillage = '';

                    if (!this.selectedDistrict) {
                        this.$wire.set('district_id', ''); this.$wire.set('district_name', '');
                        return;
                    }

                    const [id, name] = this.selectedDistrict.split('|');
                    this.$wire.set('district_id', id); this.$wire.set('district_name', name);
                    this.$wire.set('village_id', ''); this.$wire.set('village_name', '');

                    this.loading = true;
                    await this.fetchVillages(id);
                    this.loading = false;
                },

                onVillageChange() {
                    if (!this.selectedVillage) {
                        this.$wire.set('village_id', ''); this.$wire.set('village_name', '');
                        return;
                    }
                    const [id, name] = this.selectedVillage.split('|');
                    this.$wire.set('village_id', id); this.$wire.set('village_name', name);
                }
            }
        }

        function pobHandler(apiUrl) {
            return {
                apiUrl: apiUrl,
                suggestions: [],
                hasFetched: false,

                init() {
                    // Check if we have cached suggestions in session storage to avoid re-fetching
                    const cached = sessionStorage.getItem('pob_suggestions');
                    if (cached) {
                        this.suggestions = JSON.parse(cached);
                        this.hasFetched = true;
                    }
                },

                async onFocus() {
                    if (this.hasFetched) return;
                    
                    try {
                        const provRes = await fetch(`${this.apiUrl}/provinces.json`);
                        const provinces = await provRes.json();
                        
                        // We fetch regencies in chunks to avoid overwhelming the browser/API
                        // First, prioritze East Java (ID: 35) since the school is in Malang
                        const prioritizedIds = ['35', '33', '34', '32', '31', '36', '51', '52', '73', '61'];
                        
                        const fetchPromises = provinces.map(async (prov) => {
                            try {
                                const res = await fetch(`${this.apiUrl}/regencies/${prov.id}.json`);
                                return await res.json();
                            } catch (e) { return []; }
                        });

                        const results = await Promise.all(fetchPromises);
                        this.suggestions = results.flat().map(r => r.name).sort();
                        
                        // Cache the results for the session
                        sessionStorage.setItem('pob_suggestions', JSON.stringify(this.suggestions));
                        this.hasFetched = true;
                    } catch (e) {
                        console.error('Failed to fetch POB suggestions:', e);
                    }
                },

                onInput(e) {
                    let val = e.target.value;
                    if (!val) return;

                    // Auto-format abbreviations and ensure uppercase
                    let formatted = val.toUpperCase()
                        .replace(/^KAB\.?\s/i, 'KABUPATEN ')
                        .replace(/^KOTA\.?\s/i, 'KOTA ')
                        .replace(/^KTA\.?\s/i, 'KOTA ');

                    if (formatted !== val) {
                        this.$nextTick(() => {
                            // Only update if it actually changed to avoid cursor jumping
                            const start = e.target.selectionStart;
                            this.$wire.set('pob', formatted);
                            // We wait for Livewire to sync then reset cursor if needed
                            // But for simple datalist inputs, this is usually enough
                        });
                    }
                }
            }
        }
    </script>
</div>
