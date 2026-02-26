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

<div>
    <div class="p-6">
    <x-header title="Profil Sekolah" subtitle="Kelola informasi profil sekolah yang ditampilkan di website publik" separator />

    @if (session()->has('message'))
        <x-alert title="Sukses" icon="o-check-circle" class="alert-success mb-6">
            {{ session('message') }}
        </x-alert>
    @endif

    <form wire:submit="save" class="space-y-8">
        {{-- Informasi Dasar --}}
        <x-card title="Informasi Dasar" subtitle="Informasi umum tentang sekolah" separator shadow>
            <div class="space-y-4">
                <x-input 
                    wire:model="name" 
                    label="Nama Sekolah" 
                    type="text" 
                    required 
                    placeholder="Contoh: PKBM Harapan Bangsa"
                />

                <x-textarea 
                    wire:model="address" 
                    label="Alamat Lengkap" 
                    rows="3" 
                    required 
                    placeholder="Masukkan alamat lengkap sekolah"
                />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-input 
                        wire:model="phone" 
                        label="Nomor Telepon" 
                        type="text" 
                        required 
                        placeholder="Contoh: 021-12345678"
                    />

                    <x-input 
                        wire:model="email" 
                        label="Email" 
                        type="email" 
                        required 
                        placeholder="Contoh: info@sekolah.com"
                    />
                </div>

                <x-input 
                    wire:model="operating_hours" 
                    label="Jam Operasional" 
                    type="text" 
                    placeholder="Contoh: Senin - Jumat, 08:00 - 16:00"
                    icon="o-clock"
                />
            </div>
        </x-card>

        {{-- Logo Sekolah --}}
        <x-card title="Logo Sekolah" subtitle="Upload logo sekolah (maksimal 5MB, format: JPEG, PNG, WebP)" separator shadow>
            <div class="space-y-6">
                @if ($currentLogoPath)
                    <div class="flex items-start gap-4 p-4 bg-base-200 rounded-lg">
                        <img 
                            src="{{ Storage::url($currentLogoPath) }}" 
                            alt="Logo Sekolah" 
                            class="h-32 w-32 rounded-lg border border-base-300 object-contain bg-white"
                        >
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Logo saat ini</span>
                            <x-button 
                                wire:click="removeLogo" 
                                label="Hapus Logo"
                                icon="o-trash"
                                class="btn-error btn-sm"
                                wire:confirm="Apakah Anda yakin ingin menghapus logo?"
                            />
                        </div>
                    </div>
                @endif

                <x-file 
                    wire:model="logo" 
                    label="Upload Logo Baru" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    crop-after-change
                >
                     @if ($logo)
                        <div class="text-sm mt-2">
                            File dipilih: <span class="font-medium">{{ $logo->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-file>
            </div>
        </x-card>

        {{-- Visi & Misi --}}
        <x-card title="Visi & Misi" subtitle="Visi dan misi sekolah" separator shadow>
            <div class="space-y-4">
                <x-textarea 
                    wire:model="vision" 
                    label="Visi" 
                    rows="4" 
                    required 
                    placeholder="Masukkan visi sekolah"
                />

                <x-textarea 
                    wire:model="mission" 
                    label="Misi" 
                    rows="6" 
                    required 
                    placeholder="Masukkan misi sekolah"
                />
            </div>
        </x-card>

        {{-- Sejarah --}}
        <x-card title="Sejarah" subtitle="Sejarah singkat sekolah (opsional)" separator shadow>
            <x-textarea 
                wire:model="history" 
                label="Sejarah Sekolah" 
                rows="6" 
                placeholder="Masukkan sejarah singkat sekolah"
            />
        </x-card>

        {{-- Media Sosial --}}
        <x-card title="Media Sosial" subtitle="Link ke akun media sosial sekolah (opsional)" separator shadow>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input 
                    wire:model="facebook_url" 
                    label="Facebook" 
                    type="url" 
                    placeholder="https://facebook.com/namaakun"
                    icon="o-link"
                />

                <x-input 
                    wire:model="instagram_url" 
                    label="Instagram" 
                    type="url" 
                    placeholder="https://instagram.com/namaakun"
                    icon="o-link"
                />

                <x-input 
                    wire:model="youtube_url" 
                    label="YouTube" 
                    type="url" 
                    placeholder="https://youtube.com/@namaakun"
                    icon="o-link"
                />

                <x-input 
                    wire:model="twitter_url" 
                    label="Twitter/X" 
                    type="url" 
                    placeholder="https://twitter.com/namaakun"
                    icon="o-link"
                />
            </div>
        </x-card>

        {{-- Lokasi --}}
        <x-card title="Lokasi" subtitle="Koordinat lokasi untuk Google Maps (opsional)" separator shadow>
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-input 
                        wire:model="latitude" 
                        label="Latitude" 
                        type="text" 
                        placeholder="Contoh: -6.200000"
                        icon="o-map-pin"
                    />

                    <x-input 
                        wire:model="longitude" 
                        label="Longitude" 
                        type="text" 
                        placeholder="Contoh: 106.816666"
                        icon="o-map-pin"
                    />
                </div>

                <x-alert icon="o-information-circle" class="bg-base-200 border-base-300">
                    Tip: Anda bisa mendapatkan koordinat dari Google Maps dengan klik kanan pada lokasi dan pilih koordinat.
                </x-alert>
            </div>
        </x-card>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-4 pt-6">
            <x-button 
                label="Simpan Profil"
                class="btn-primary" 
                type="submit" 
                spinner="save"
            />
        </div>
    </form>
</div>
</div>
