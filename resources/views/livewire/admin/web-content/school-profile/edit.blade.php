<?php

use App\Models\SchoolProfile;
use App\Services\CacheService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?SchoolProfile $profile = null;
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $vision = '';
    public string $mission = '';
    public string $history = '';
    public string $operating_hours = '';
    public string $facebook_url = '';
    public string $instagram_url = '';
    public string $youtube_url = '';
    public string $twitter_url = '';
    public string $latitude = '';
    public string $longitude = '';
    public $logo;
    public ?string $currentLogoPath = null;

    public function mount(): void
    {
        $this->profile = SchoolProfile::active();

        if ($this->profile) {
            $this->name = $this->profile->name ?? '';
            $this->address = $this->profile->address ?? '';
            $this->phone = $this->profile->phone ?? '';
            $this->email = $this->profile->email ?? '';
            $this->vision = $this->profile->vision ?? '';
            $this->mission = $this->profile->mission ?? '';
            $this->history = $this->profile->history ?? '';
            $this->operating_hours = $this->profile->operating_hours ?? '';
            $this->facebook_url = $this->profile->facebook_url ?? '';
            $this->instagram_url = $this->profile->instagram_url ?? '';
            $this->youtube_url = $this->profile->youtube_url ?? '';
            $this->twitter_url = $this->profile->twitter_url ?? '';
            $this->latitude = $this->profile->latitude ?? '';
            $this->longitude = $this->profile->longitude ?? '';
            $this->currentLogoPath = $this->profile->logo_path;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'vision' => 'required|string',
            'mission' => 'required|string',
            'history' => 'nullable|string',
            'operating_hours' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nama sekolah',
            'address' => 'alamat',
            'phone' => 'nomor telepon',
            'email' => 'email',
            'vision' => 'visi',
            'mission' => 'misi',
            'history' => 'sejarah',
            'operating_hours' => 'jam operasional',
            'facebook_url' => 'URL Facebook',
            'instagram_url' => 'URL Instagram',
            'youtube_url' => 'URL YouTube',
            'twitter_url' => 'URL Twitter',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'logo' => 'logo',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (!$this->profile) {
            $this->profile = new SchoolProfile();
        }

        // Handle logo upload
        if ($this->logo) {
            // Delete old logo if exists
            if ($this->profile->logo_path) {
                Storage::disk('public')->delete($this->profile->logo_path);
            }

            $path = $this->logo->store('school-profile', 'public');
            $this->profile->logo_path = $path;
            $this->currentLogoPath = $path;
        }

        // Update profile data
        $this->profile->fill([
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'vision' => $this->vision,
            'mission' => $this->mission,
            'history' => $this->history,
            'operating_hours' => $this->operating_hours,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'youtube_url' => $this->youtube_url,
            'twitter_url' => $this->twitter_url,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'is_active' => true,
        ]);

        $this->profile->save();

        // Clear cache after saving
        $cacheService = app(CacheService::class);
        $cacheService->clearSchoolProfileCache();

        // Reset logo upload field
        $this->logo = null;

        session()->flash('message', 'Profil sekolah berhasil disimpan.');
    }

    public function removeLogo(): void
    {
        if ($this->profile && $this->profile->logo_path) {
            Storage::disk('public')->delete($this->profile->logo_path);
            $this->profile->logo_path = null;
            $this->profile->save();
            $this->currentLogoPath = null;

            session()->flash('message', 'Logo berhasil dihapus.');
        }
    }
}; ?>

<div class="p-6 space-y-8 text-slate-900 dark:text-white pb-24 md:pb-6">
    @if (session()->has('message'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('message') }}
        </x-ui.alert>
    @endif

    <x-ui.header :title="__('Profil Sekolah')" :subtitle="__('Kelola informasi identitas dan profil sekolah yang akan ditampilkan pada portal publik.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Fasilitas')" icon="o-building-office" class="btn-ghost" :href="route('admin.school-profile.facilities')" wire:navigate />
            <x-ui.button :label="__('Struktur')" icon="o-user-group" class="btn-ghost" :href="route('admin.school-profile.staff-members')" wire:navigate />
            <x-ui.button :label="__('Simpan Perubahan')" icon="o-check" class="btn-primary shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.header>

    <form wire:submit="save" class="space-y-8 max-w-5xl mx-auto">
        {{-- Informasi Dasar --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Informasi Dasar & Kontak') }}</h3>
            </div>
            <div class="p-8 space-y-6">
                <x-ui.input 
                    wire:model="name" 
                    :label="__('Nama Lengkap Sekolah / Lembaga')" 
                    type="text" 
                    required 
                    :placeholder="__('Contoh: PKBM Harapan Bangsa')"
                    class="font-semibold text-lg"
                />

                <x-ui.textarea 
                    wire:model="address" 
                    :label="__('Alamat Domisili Lengkap')" 
                    rows="3" 
                    required 
                    :placeholder="__('Masukkan alamat lengkap termasuk kode pos...')"
                    class="mt-1"
                />

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-ui.input 
                        wire:model="phone" 
                        :label="__('Nomor Telepon / WhatsApp')" 
                        type="text" 
                        required 
                        :placeholder="__('Contoh: 021-12345678')"
                        icon="o-phone"
                    />

                    <x-ui.input 
                        wire:model="email" 
                        :label="__('Alamat Email Resmi')" 
                        type="email" 
                        required 
                        :placeholder="__('Contoh: admin@sekolah.sch.id')"
                        icon="o-envelope"
                    />
                </div>

                <x-ui.input 
                    wire:model="operating_hours" 
                    :label="__('Jam Layanan Operasional')" 
                    type="text" 
                    :placeholder="__('Contoh: Senin - Jumat, 08:00 - 15:00 WIB')"
                    icon="o-clock"
                    class="bg-slate-50 border-none shadow-none text-sm"
                />
            </div>
        </x-ui.card>

        {{-- Logo Sekolah --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Identitas Visual (Logo)') }}</h3>
            </div>
            <div class="p-8 space-y-8">
                @if ($currentLogoPath)
                    <div class="flex flex-col md:flex-row items-center gap-8 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800 group">
                        <div class="relative shrink-0">
                            <img 
                                src="{{ Storage::url($currentLogoPath) }}" 
                                alt="Logo Sekolah" 
                                class="h-32 w-32 rounded-2xl border border-white dark:border-slate-700 object-contain bg-white shadow-xl group-hover:scale-105 transition-transform duration-500"
                            >
                            <div class="absolute -right-2 -top-2 size-6 bg-emerald-500 rounded-full flex items-center justify-center border-2 border-white shadow-sm ring-4 ring-emerald-50">
                                <x-ui.icon name="o-check" class="size-3 text-white" />
                            </div>
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-1 uppercase tracking-wider text-xs">{{ __('Logo Sekolah Saat Ini') }}</h4>
                            <p class="text-[10px] text-slate-400 font-mono tracking-tighter mb-4">{{ basename($currentLogoPath) }}</p>
                            <x-ui.button 
                                wire:click="removeLogo" 
                                :label="__('Hapus Logo Permanen')"
                                icon="o-trash"
                                class="btn-ghost btn-xs text-rose-500 hover:bg-rose-50 font-semibold"
                                wire:confirm="{{ __('Apakah Anda yakin ingin menghapus logo sekolah?') }}"
                            />
                        </div>
                    </div>
                @endif

                <div class="max-w-lg space-y-4">
                    <x-ui.file 
                        wire:model="logo" 
                        :label="__('Ganti / Unggah Logo Baru')" 
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                    >
                         @if ($logo)
                            <div class="text-[10px] font-black italic text-indigo-600 mt-2 px-1">
                                {{ __('File dipilih') }}: <span class="underline">{{ $logo->getClientOriginalName() }}</span>
                            </div>
                        @endif
                    </x-ui.file>
                </div>
                <p class="text-xs text-slate-400 px-1 leading-relaxed">
                    * {{ __('Format file yang didukung: JPEG, PNG, WebP (Maksimal 5MB). Pastikan logo memiliki background transparan untuk tampilan terbaik.') }}
                </p>
            </div>
        </x-ui.card>

        {{-- Visi & Misi --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Budaya & Filosofi (Visi & Misi)') }}</h3>
            </div>
            <div class="p-8 space-y-6">
                <x-ui.textarea 
                    wire:model="vision" 
                    :label="__('Visi Sekolah / Harapan Masa Depan')" 
                    rows="3" 
                    required 
                    :placeholder="__('Masukkan visi besar sekolah di sini...')"
                    class="font-semibold text-sm text-indigo-600"
                />

                <x-ui.textarea 
                    wire:model="mission" 
                    :label="__('Misi Utama Sekolah')" 
                    rows="6" 
                    required 
                    :placeholder="__('Masukkan poin-poin misi sekolah...')"
                    class="text-sm border-slate-100"
                />
            </div>
        </x-ui.card>

        {{-- Sejarah --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Rekam Jejak & Sejarah') }}</h3>
            </div>
            <div class="p-8">
                <x-ui.textarea 
                    wire:model="history" 
                    :label="__('Narasi Sejarah Sekolah')" 
                    rows="6" 
                    :placeholder="__('Ceritakan perjalanan singkat sekolah sejak berdiri...')"
                    class="text-sm bg-slate-50/50 border-none leading-relaxed"
                />
            </div>
        </x-ui.card>

        {{-- Media Sosial --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Portofolio Media Sosial') }}</h3>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input 
                        wire:model="facebook_url" 
                        label="Facebook" 
                        type="url" 
                        placeholder="https://facebook.com/namaakun"
                        icon="o-link"
                    />

                    <x-ui.input 
                        wire:model="instagram_url" 
                        label="Instagram" 
                        type="url" 
                        placeholder="https://instagram.com/namaakun"
                        icon="o-link"
                    />

                    <x-ui.input 
                        wire:model="youtube_url" 
                        label="YouTube" 
                        type="url" 
                        placeholder="https://youtube.com/@namaakun"
                        icon="o-link"
                    />

                    <x-ui.input 
                        wire:model="twitter_url" 
                        label="Twitter / X" 
                        type="url" 
                        placeholder="https://twitter.com/namaakun"
                        icon="o-link"
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Lokasi --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Integrasi Peta & Lokasi') }}</h3>
                <x-ui.icon name="o-map-pin" class="size-5 text-rose-500" />
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-ui.input 
                        wire:model="latitude" 
                        label="Latitude" 
                        type="text" 
                        placeholder="Contoh: -6.200000"
                        class="font-mono text-xs"
                    />

                    <x-ui.input 
                        wire:model="longitude" 
                        label="Longitude" 
                        type="text" 
                        placeholder="Contoh: 106.816666"
                        class="font-mono text-xs"
                    />
                </div>

                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-4">
                    <x-ui.icon name="o-information-circle" class="size-6 text-amber-500" />
                    <p class="text-xs text-amber-800 leading-relaxed font-semibold">
                        {{ __('Tip: Anda bisa mendapatkan koordinat dari Google Maps dengan klik kanan pada lokasi sekolah di peta dan pilih "What\'s here?" atau klik langsung pada angka koordinat yang muncul.') }}
                    </p>
                </div>
            </div>
        </x-ui.card>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-4 py-12 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button 
                :label="__('Batalkan Perubahan')"
                class="btn-ghost" 
                wire:click="$refresh"
            />
            <x-ui.button 
                :label="__('Simpan Seluruh Profil')"
                class="btn-primary shadow-2xl shadow-primary/30 px-12 py-4" 
                type="submit" 
                spinner="save"
            />
        </div>
    </form>
</div>
