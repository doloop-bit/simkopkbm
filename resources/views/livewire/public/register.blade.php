<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.public.layouts.public')] class extends Component
{
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

    public function updatedProvinceId($id): void
    {
        $this->province_name = \Laravolt\Indonesia\Models\Province::where('code', $id)->first()?->name ?? '';
        $this->regency_id = '';
        $this->regency_name = '';
        $this->district_id = '';
        $this->district_name = '';
        $this->village_id = '';
        $this->village_name = '';
    }

    public function updatedRegencyId($id): void
    {
        $this->regency_name = \Laravolt\Indonesia\Models\City::where('code', $id)->first()?->name ?? '';
        $this->district_id = '';
        $this->district_name = '';
        $this->village_id = '';
        $this->village_name = '';
    }

    public function updatedDistrictId($id): void
    {
        $this->district_name = \Laravolt\Indonesia\Models\District::where('code', $id)->first()?->name ?? '';
        $this->village_id = '';
        $this->village_name = '';
    }

    public function updatedVillageId($id): void
    {
        $this->village_name = \Laravolt\Indonesia\Models\Village::where('code', $id)->first()?->name ?? '';
    }

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
        if (! empty($this->extra_field)) {
            abort(422, 'Spam detected.');
        }

        // 2. Rate limiting (max 3 submissions per 30 minutes per IP)
        $key = 'registration-submit:'.$request->ip();
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
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'provinces' => \Laravolt\Indonesia\Models\Province::orderBy('name')->get(),
            'regencies' => $this->province_id ? \Laravolt\Indonesia\Models\City::where('province_code', $this->province_id)->orderBy('name')->get() : [],
            'districts' => $this->regency_id ? \Laravolt\Indonesia\Models\District::where('city_code', $this->regency_id)->orderBy('name')->get() : [],
            'villages' => $this->district_id ? \Laravolt\Indonesia\Models\Village::where('district_code', $this->district_id)->orderBy('name')->get() : [],

            'title' => 'Pendaftaran - '.config('app.name'),
            'description' => 'Daftar sebagai siswa baru di '.config('app.name'),
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
            <x-ui.badge :label="__('Penerimaan Siswa Baru')" class="bg-primary/20 text-primary-foreground border-none font-bold text-xs px-4 py-1.5 uppercase tracking-widest" />
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold font-heading tracking-tight uppercase leading-none">
                Formulir <span class="text-primary truncate">Registrasi</span>
            </h1>
            <p class="text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                {{ __('Silakan lengkapi seluruh informasi yang dibutuhkan untuk memulai perjalanan akademik Anda bersama kami.') }}
            </p>
            <div class="w-24 h-1 bg-primary mx-auto rounded-full shadow-lg shadow-primary/20"></div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        @if($submitted)
            <!-- Premium Success State -->
            <div class="text-center py-20 animate-in zoom-in duration-700">
                <div class="w-24 h-24 bg-emerald-50 dark:bg-emerald-950 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-emerald-500/20 rotate-3 group hover:rotate-0 transition-transform duration-500">
                    <x-ui.icon name="o-check-circle" class="size-12 text-emerald-500" />
                </div>
                
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white uppercase tracking-tight mb-4">
                    {{ __('Pendaftaran Terkirim!') }}
                </h2>
                
                <div class="max-w-md mx-auto p-8 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 mb-10">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">{{ __('ID REGISTRASI ANDA') }}</p>
                    <div class="text-4xl font-bold text-primary font-mono tracking-tight drop-shadow-sm mb-4">
                        {{ $registrationNumber }}
                    </div>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">
                        {{ __('Simpan nomor pendaftaran ini sebagai bukti registrasi. Tim administrasi kami akan segera meninjau data Anda.') }}
                    </p>
                </div>

                <x-ui.button 
                    href="{{ route('home') }}" 
                    :label="__('Kembali ke Beranda')" 
                    icon="o-arrow-left" 
                    class="btn-primary px-8 py-3 rounded-2xl shadow-xl shadow-primary/20 font-bold tracking-tight uppercase" 
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
                                <span class="text-xs font-bold uppercase tracking-wider transition-colors duration-500
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
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight leading-none">{{ __('Profil Personal Calon Siswa') }}</h2>
                                <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Informasi fundamental identitas diri') }}</p>
                            </div>
 
                            <div class="space-y-6">
                                <x-ui.input 
                                    wire:model="name"
                                    :label="__('Nama Lengkap (Sesuai Akta Lahir)')"
                                    :placeholder="__('Masukkan nama lengkap Anda...')"
                                    class="font-semibold uppercase tracking-tight"
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
                                        class="font-semibold uppercase tracking-tight"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-ui.input 
                                            wire:model="pob"
                                            list="pob-suggestions"
                                            :label="__('Tempat Kelahiran')"
                                            :placeholder="__('KOTA/KABUPATEN KELAHIRAN')"
                                            class="font-semibold uppercase tracking-tight"
                                        />
                                        <datalist id="pob-suggestions" class="hidden">
                                            @foreach(\Laravolt\Indonesia\Models\City::all(['name']) as $city)
                                                <option value="{{ $city->name }}"></option>
                                            @endforeach
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
                        <div wire:key="step-2" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight leading-none">{{ __('Data Domisili & Wilayah') }}</h2>
                                <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Lokasi tempat tinggal calon siswa saat ini') }}</p>
                            </div>
 
                            <div class="space-y-6">
                                <x-ui.textarea 
                                    wire:model="address" 
                                    :label="__('Alamat Lengkap (Jl, No Rumah, RT/RW)')"
                                    :placeholder="__('Tuliskan alamat lengkap...')"
                                    rows="3"
                                    class="font-semibold uppercase tracking-tight"
                                />
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">{{ __('Provinsi') }}</label>
                                        <select wire:model.live="province_id"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-bold uppercase tracking-tight text-sm focus:ring-primary transition-all appearance-none outline-none">
                                            <option value="">{{ __('Pilih Provinsi...') }}</option>
                                            @foreach($provinces as $prov)
                                                <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                                            @endforeach

                                        </select>
                                    </div>
 
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">{{ __('Kabupaten / Kota') }}</label>
                                        <select wire:model.live="regency_id"
                                            @disabled(empty($regencies))
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-bold uppercase tracking-tight text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kabupaten/Kota...') }}</option>
                                            @foreach($regencies as $reg)
                                                <option value="{{ $reg->code }}">{{ $reg->name }}</option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">{{ __('Kecamatan') }}</label>
                                        <select wire:model.live="district_id"
                                            @disabled(empty($districts))
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-bold uppercase tracking-tight text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kecamatan...') }}</option>
                                            @foreach($districts as $dist)
                                                <option value="{{ $dist->code }}">{{ $dist->name }}</option>
                                            @endforeach

                                        </select>
                                    </div>
 
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">{{ __('Kelurahan / Desa') }}</label>
                                        <select wire:model.live="village_id"
                                            @disabled(empty($villages))
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 ring-slate-100 dark:ring-slate-700 rounded-2xl px-5 py-4 font-bold uppercase tracking-tight text-sm focus:ring-primary transition-all appearance-none outline-none disabled:opacity-50">
                                            <option value="">{{ __('Pilih Kelurahan/Desa...') }}</option>
                                            @foreach($villages as $vil)
                                                <option value="{{ $vil->code }}">{{ $vil->name }}</option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @endif

                        <!-- Step 3: Guardian & Family Dynamic -->
                        @if ($currentStep === 3)
                        <div wire:key="step-3" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight leading-none">{{ __('Data Orang Tua / Wali') }}</h2>
                                <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Informasi penanggung jawab & asal usul keluarga') }}</p>
                            </div>
 
                            <div class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.input 
                                        wire:model="father_name"
                                        :label="__('Nama Lengkap Ayah Kandung')"
                                        :placeholder="__('Nama sesuai dokumen resmi')"
                                        class="font-semibold uppercase tracking-tight"
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
                                        class="font-semibold uppercase tracking-tight"
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
                                        class="font-semibold uppercase tracking-tight"
                                    />
                                </div>
 
                                <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700 space-y-6">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Opsi Penanggung Jawab Lain / Wali') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <x-ui.input 
                                            wire:model="guardian_name"
                                            :label="__('Nama Lengkap Wali')"
                                            :placeholder="__('Opsional jika ada wali')"
                                            class="font-semibold uppercase tracking-tight"
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
                                        class="font-bold"
                                    />
                                    <x-ui.input 
                                        type="number" 
                                        wire:model="total_siblings" 
                                        min="0"
                                        :label="__('Jumlah Saudara Kandung')"
                                        class="font-bold"
                                    />
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 4: Academic Foundation & Review -->
                        @if ($currentStep === 4)
                        <div wire:key="step-4" class="space-y-10 animate-in fade-in slide-in-from-right-4 duration-700">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800 dark:text-white uppercase tracking-tight leading-none">{{ __('Data Akademik & Preferensi') }}</h2>
                                <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ __('Tahap akhir verifikasi informasi akademik') }}</p>
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
                                        class="font-semibold uppercase tracking-tight"
                                    />
                                </div>
 
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-ui.select 
                                        wire:model="preferred_level_id"
                                        :label="__('Jenjang yang Diminati')"
                                        :options="$levels"
                                        class="font-semibold uppercase tracking-tight"
                                    />
                                    <x-ui.select 
                                        wire:model="academic_year_id"
                                        :label="__('Periode Tahun Ajaran')"
                                        :options="$academicYears"
                                        class="font-semibold uppercase tracking-tight"
                                    />
                                </div>
                            </div>

                            <!-- Sophisticated Summary Preview -->
                            <div class="mt-12 p-8 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-8 opacity-5">
                                    <x-ui.icon name="o-check-badge" class="size-32" />
                                </div>
 
                                <h3 class="text-xs font-bold text-primary uppercase tracking-widest mb-8 flex items-center gap-3">
                                    <span class="w-8 h-px bg-primary/20"></span>
                                    {{ __('Ringkasan Konfirmasi Registrasi') }}
                                </h3>
 
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-4 relative text-slate-800 dark:text-slate-200">
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Nama Lengkap') }}</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight text-right">{{ $name ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Identitas (NIK)') }}</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono text-right">{{ $nik ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Domisili TTL') }}</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight text-right">{{ $pob ?: '-' }}, {{ $dob ? \Carbon\Carbon::parse($dob)->format('d/m/Y') : '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Domisili Aktif') }}</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight text-right overflow-hidden text-ellipsis whitespace-nowrap max-w-[200px]">{{ $address ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Orang Tua (A/I)') }}</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-tight text-right">{{ $father_name ?: '-' }} / {{ $mother_name ?: '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Tahun Ajaran') }}</span>
                                            @php $selectedYear = $academic_year_id ? collect($academicYears)->firstWhere('id', (int)$academic_year_id) : null; @endphp
                                            <span class="text-sm font-bold text-primary uppercase tracking-tight text-right">{{ $selectedYear?->name ?? '-' }}</span>
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
                                    class="w-full sm:w-auto btn-ghost text-slate-400 font-bold uppercase tracking-tight px-8" 
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
                                    class="w-full sm:w-auto btn-primary shadow-xl shadow-primary/20 px-10 font-bold uppercase tracking-tight" 
                                />
                            @else
                                <x-ui.button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    :label="__('Submit Pendaftaran')" 
                                    icon="o-paper-airplane" 
                                    class="w-full sm:w-auto btn-primary bg-emerald-500 hover:bg-emerald-600 border-none shadow-xl shadow-emerald-500/20 px-12 font-bold uppercase tracking-tight py-6 h-auto" 
                                    spinner="submit"
                                />
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    </script>
</div>
