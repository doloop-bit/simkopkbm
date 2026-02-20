<?php

declare(strict_types=1);

use App\Models\{Registration, Level, AcademicYear};
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
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
    <!-- Page Header -->
    <div class="relative bg-slate-900 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="reg-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#reg-grid)" />
            </svg>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold font-heading mb-4">Pendaftaran Siswa Baru</h1>
                <p class="text-lg sm:text-xl md:text-2xl text-slate-300">
                    Isi formulir di bawah untuk mendaftar
                </p>
                <div class="w-24 h-1 bg-amber-500 mx-auto mt-6 rounded-full"></div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        @if($submitted)
            <!-- Success State -->
            <div class="text-center py-16">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">Pendaftaran Berhasil!</h2>
                <p class="text-gray-600 mb-2">Nomor pendaftaran Anda:</p>
                <p class="text-3xl font-bold text-amber-600 mb-6">{{ $registrationNumber }}</p>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Simpan nomor pendaftaran ini. Kami akan menghubungi Anda untuk informasi selanjutnya.
                </p>
                <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition-colors" wire:navigate>
                    Kembali ke Beranda
                </a>
            </div>
        @else
            <!-- Step Indicators -->
            <div class="mb-10">
                <div class="flex items-center justify-between relative">
                    <!-- Progress Line -->
                    <div class="absolute top-5 left-0 right-0 h-0.5 bg-gray-200">
                        <div class="h-full bg-amber-500 transition-all duration-500"
                             style="width: {{ ($currentStep - 1) / ($totalSteps - 1) * 100 }}%"></div>
                    </div>

                    @php
                        $steps = [
                            ['label' => 'Data Pribadi', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['label' => 'Alamat', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                            ['label' => 'Orang Tua', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                            ['label' => 'Akademik', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
                        ];
                    @endphp

                    @foreach($steps as $index => $step)
                        <button wire:click="goToStep({{ $index + 1 }})"
                                class="relative z-10 flex flex-col items-center {{ $index + 1 <= $currentStep ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300
                                {{ $index + 1 < $currentStep ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : '' }}
                                {{ $index + 1 === $currentStep ? 'bg-amber-500 text-white ring-4 ring-amber-100 shadow-lg' : '' }}
                                {{ $index + 1 > $currentStep ? 'bg-gray-200 text-gray-500' : '' }}">
                                @if($index + 1 < $currentStep)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                                    </svg>
                                @endif
                            </div>
                            <span class="mt-2 text-xs font-semibold hidden sm:block {{ $index + 1 <= $currentStep ? 'text-amber-600' : 'text-gray-400' }}">
                                {{ $step['label'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-8 md:p-10">
                    <form wire:submit="submit">
                        {{-- Honeypot field --}}
                        <div class="hidden" aria-hidden="true">
                            <input type="text" wire:model="extra_field" tabindex="-1" autocomplete="off">
                        </div>

                        @error('submit')
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                                {{ $message }}
                            </div>
                        @enderror

                        <!-- Step 1: Data Pribadi -->
                        @if ($currentStep === 1)
                        <div wire:key="step-1" x-transition>
                            <h2 class="text-xl font-bold text-gray-900 mb-1">Data Pribadi</h2>
                            <p class="text-gray-500 text-sm mb-6">Informasi dasar calon siswa</p>

                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Nama Lengkap <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="name"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                       placeholder="Nama lengkap sesuai akta lahir">
                                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- NIK -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">NIK</label>
                                        <input type="text" wire:model="nik" maxlength="16"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="16 digit NIK">
                                    </div>

                                    <!-- Gender -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis Kelamin</label>
                                        <select wire:model="gender"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                            <option value="">Pilih...</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- POB -->
                                    <div x-data="pobHandler('{{ $wilayahApiUrl }}')" x-init="init()">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tempat Lahir</label>
                                        <input type="text" 
                                           wire:model="pob"
                                           x-ref="pobInput"
                                           x-on:focus="onFocus()"
                                           x-on:input="onInput($event)"
                                           list="pob-suggestions"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors uppercase"
                                           placeholder="KOTA/KABUPATEN KELAHIRAN">
                                        <datalist id="pob-suggestions">
                                            <template x-for="suggestion in suggestions" :key="suggestion">
                                                <option :value="suggestion"></option>
                                            </template>
                                        </datalist>
                                        
                                    </div>

                                    <!-- DOB -->
                                    <x-date wire:model="dob" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                        label="Tanggal Lahir"
                                        format="DD/MM/YYYY"
                                        placeholder="Pilih tanggal lahir..."
                                        helpers />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Phone -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">No. HP</label>
                                        <input type="tel" wire:model="phone"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="08xxxxxxxxxx">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                        <input type="email" wire:model="email"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="email@contoh.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 2: Alamat -->
                        @if ($currentStep === 2)
                        <div wire:key="step-2" x-transition
                             x-data="addressPicker('{{ $wilayahApiUrl }}')"
                             x-init="init()">
                            <h2 class="text-xl font-bold text-gray-900 mb-1">Alamat</h2>
                            <p class="text-gray-500 text-sm mb-6">Alamat tempat tinggal saat ini</p>

                            <div class="space-y-4">
                                <!-- Full Address -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                                    <textarea wire:model="address" rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                          placeholder="Jalan, nomor rumah, RT/RW..."></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Province -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Provinsi</label>
                                        <select x-model="selectedProvince" @change="onProvinceChange()"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                            <option value="">Pilih Provinsi...</option>
                                            <template x-for="prov in provinces" :key="prov.id">
                                                <option :value="prov.id + '|' + prov.name" x-text="prov.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Regency -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kabupaten/Kota</label>
                                        <select x-model="selectedRegency" @change="onRegencyChange()"
                                            :disabled="regencies.length === 0"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors disabled:bg-gray-100">
                                            <option value="">Pilih Kabupaten/Kota...</option>
                                            <template x-for="reg in regencies" :key="reg.id">
                                                <option :value="reg.id + '|' + reg.name" x-text="reg.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- District -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kecamatan</label>
                                        <select x-model="selectedDistrict" @change="onDistrictChange()"
                                            :disabled="districts.length === 0"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors disabled:bg-gray-100">
                                            <option value="">Pilih Kecamatan...</option>
                                            <template x-for="dist in districts" :key="dist.id">
                                                <option :value="dist.id + '|' + dist.name" x-text="dist.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Village -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kelurahan/Desa</label>
                                        <select x-model="selectedVillage" @change="onVillageChange()"
                                            :disabled="villages.length === 0"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors disabled:bg-gray-100">
                                            <option value="">Pilih Kelurahan/Desa...</option>
                                            <template x-for="vil in villages" :key="vil.id">
                                                <option :value="vil.id + '|' + vil.name" x-text="vil.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <!-- Loading indicator -->
                                <div x-show="loading" class="flex items-center text-sm text-amber-600">
                                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Memuat data wilayah...
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 3: Data Orang Tua/Wali -->
                        @if ($currentStep === 3)
                        <div wire:key="step-3" x-transition>
                            <h2 class="text-xl font-bold text-gray-900 mb-1">Data Orang Tua / Wali</h2>
                            <p class="text-gray-500 text-sm mb-6">Informasi orang tua dan wali</p>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Ayah</label>
                                        <input type="text" wire:model="father_name"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="Nama lengkap ayah">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">NIK Ayah</label>
                                        <input type="text" wire:model="nik_ayah" maxlength="16"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="16 digit NIK ayah">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Ibu</label>
                                        <input type="text" wire:model="mother_name"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="Nama lengkap ibu">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">NIK Ibu</label>
                                        <input type="text" wire:model="nik_ibu" maxlength="16"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="16 digit NIK ibu">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">No. KK</label>
                                        <input type="text" wire:model="no_kk" maxlength="16"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="16 digit nomor KK">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">No. Akta Lahir</label>
                                        <input type="text" wire:model="no_akta"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="Nomor akta lahir">
                                    </div>
                                </div>

                                <div class="border-t pt-4 mt-2">
                                    <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-3">Data Wali (Opsional)</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Wali</label>
                                            <input type="text" wire:model="guardian_name"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                               placeholder="Nama wali (jika berbeda dari orang tua)">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">No. HP Wali</label>
                                            <input type="tel" wire:model="guardian_phone"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                               placeholder="08xxxxxxxxxx">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4 mt-2">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Anak ke-</label>
                                        <input type="number" wire:model="birth_order" min="1"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="1">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jumlah Saudara</label>
                                        <input type="number" wire:model="total_siblings" min="0"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 4: Data Akademik -->
                        @if ($currentStep === 4)
                        <div wire:key="step-4" x-transition>
                            <h2 class="text-xl font-bold text-gray-900 mb-1">Data Akademik</h2>
                            <p class="text-gray-500 text-sm mb-6">Informasi akademik dan jenjang yang diminati</p>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">NISN</label>
                                        <input type="text" wire:model="nisn" maxlength="10"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="10 digit NISN (jika ada)">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Asal Sekolah</label>
                                        <input type="text" wire:model="previous_school"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                           placeholder="Nama sekolah sebelumnya (jika pindahan)">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jenjang yang Diminati</label>
                                        <select wire:model="preferred_level_id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                            <option value="">Pilih Jenjang...</option>
                                            @foreach($levels as $level)
                                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tahun Ajaran</label>
                                        <select wire:model="academic_year_id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                            <option value="">Pilih Tahun Ajaran...</option>
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name ?? $year->start_year . '/' . $year->end_year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Preview -->
                            <div class="mt-8 p-5 bg-amber-50 border border-amber-200 rounded-xl">
                                <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide mb-3">Ringkasan Data</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">Nama</span>
                                            <span class="font-medium text-gray-900">: {{ $name ?: '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">NIK</span>
                                            <span class="font-medium text-gray-900">: {{ $nik ?: '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">TTL</span>
                                            <span class="font-medium text-gray-900">: {{ $pob ?: '-' }}, {{ $dob ? \Carbon\Carbon::parse($dob)->format('d/m/Y') : '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">JK</span>
                                            <span class="font-medium text-gray-900">: {{ $gender === 'L' ? 'Laki-laki' : ($gender === 'P' ? 'Perempuan' : '-') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">Alamat</span>
                                            <span class="font-medium text-gray-900">: {{ $address ?: '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">Wilayah</span>
                                            <span class="font-medium text-gray-900">: {{ collect([$village_name, $district_name, $regency_name, $province_name])->filter()->implode(', ') ?: '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">Ayah</span>
                                            <span class="font-medium text-gray-900">: {{ $father_name ?: '-' }}</span>
                                        </div>
                                        <div class="flex pb-1 border-b border-amber-100/50">
                                            <span class="text-gray-500 w-24 shrink-0">Ibu</span>
                                            <span class="font-medium text-gray-900">: {{ $mother_name ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Navigation Buttons -->
                        <div class="flex items-center justify-between mt-8 pt-6 border-t">
                            @if($currentStep > 1)
                                <button type="button" wire:click="previousStep"
                                    class="inline-flex items-center px-5 py-2.5 rounded-xl border-2 border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Sebelumnya
                                </button>
                            @else
                                <div></div>
                            @endif

                            @if($currentStep < $totalSteps)
                                <button type="button" wire:click="nextStep"
                                    class="inline-flex items-center px-6 py-2.5 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/30 transition-all transform hover:-translate-y-0.5">
                                    Selanjutnya
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            @else
                                <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center px-6 py-2.5 rounded-xl bg-green-600 text-white font-bold shadow-lg shadow-green-600/20 hover:shadow-green-600/40 hover:bg-green-700 transition-all transform hover:-translate-y-0.5 disabled:opacity-50">
                                    <span wire:loading.remove>
                                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Kirim Pendaftaran
                                    </span>
                                    <span wire:loading class="flex items-center">
                                        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Mengirim...
                                    </span>
                                </button>
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
