<?php

use App\Models\SchoolProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
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
    <flux:heading size="xl">Profil Sekolah</flux:heading>
    <flux:subheading>Kelola informasi profil sekolah yang ditampilkan di website publik</flux:subheading>

    @if (session()->has('message'))
        <flux:callout color="green" icon="check-circle" class="mt-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="mt-6 space-y-8">
        {{-- Informasi Dasar --}}
        <div class="space-y-4">
            <flux:heading size="lg">Informasi Dasar</flux:heading>
            <flux:subheading class="mb-4">Informasi umum tentang sekolah</flux:subheading>

            <flux:input 
                wire:model="name" 
                label="Nama Sekolah" 
                type="text" 
                required 
                placeholder="Contoh: PKBM Harapan Bangsa"
            />

            <flux:textarea 
                wire:model="address" 
                label="Alamat Lengkap" 
                rows="3" 
                required 
                placeholder="Masukkan alamat lengkap sekolah"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
                    wire:model="phone" 
                    label="Nomor Telepon" 
                    type="text" 
                    required 
                    placeholder="Contoh: 021-12345678"
                />

                <flux:input 
                    wire:model="email" 
                    label="Email" 
                    type="email" 
                    required 
                    placeholder="Contoh: info@sekolah.com"
                />
            </div>

            <flux:input 
                wire:model="operating_hours" 
                label="Jam Operasional" 
                type="text" 
                placeholder="Contoh: Senin - Jumat, 08:00 - 16:00"
            />
        </div>

        <flux:separator />

        {{-- Logo Sekolah --}}
        <div class="space-y-4">
            <flux:heading size="lg">Logo Sekolah</flux:heading>
            <flux:subheading class="mb-4">Upload logo sekolah (maksimal 5MB, format: JPEG, PNG, WebP)</flux:subheading>

            @if ($currentLogoPath)
                <div class="flex items-start gap-4">
                    <img 
                        src="{{ Storage::url($currentLogoPath) }}" 
                        alt="Logo Sekolah" 
                        class="h-32 w-32 rounded-lg border object-contain"
                    >
                    <div class="flex flex-col gap-2">
                        <flux:text>Logo saat ini</flux:text>
                        <flux:button 
                            wire:click="removeLogo" 
                            variant="danger" 
                            size="sm"
                            type="button"
                            wire:confirm="Apakah Anda yakin ingin menghapus logo?"
                        >
                            Hapus Logo
                        </flux:button>
                    </div>
                </div>
            @endif

            <div>
                <flux:input 
                    wire:model="logo" 
                    label="Upload Logo Baru" 
                    type="file" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                />
                @if ($logo)
                    <flux:text class="mt-2 text-sm">
                        File dipilih: {{ $logo->getClientOriginalName() }}
                    </flux:text>
                @endif
            </div>

            <div wire:loading wire:target="logo" class="text-sm text-zinc-600 dark:text-zinc-400">
                Mengunggah file...
            </div>
        </div>

        <flux:separator />

        {{-- Visi & Misi --}}
        <div class="space-y-4">
            <flux:heading size="lg">Visi & Misi</flux:heading>
            <flux:subheading class="mb-4">Visi dan misi sekolah</flux:subheading>

            <flux:textarea 
                wire:model="vision" 
                label="Visi" 
                rows="4" 
                required 
                placeholder="Masukkan visi sekolah"
            />

            <flux:textarea 
                wire:model="mission" 
                label="Misi" 
                rows="6" 
                required 
                placeholder="Masukkan misi sekolah"
            />
        </div>

        <flux:separator />

        {{-- Sejarah --}}
        <div class="space-y-4">
            <flux:heading size="lg">Sejarah</flux:heading>
            <flux:subheading class="mb-4">Sejarah singkat sekolah (opsional)</flux:subheading>

            <flux:textarea 
                wire:model="history" 
                label="Sejarah Sekolah" 
                rows="6" 
                placeholder="Masukkan sejarah singkat sekolah"
            />
        </div>

        <flux:separator />

        {{-- Media Sosial --}}
        <div class="space-y-4">
            <flux:heading size="lg">Media Sosial</flux:heading>
            <flux:subheading class="mb-4">Link ke akun media sosial sekolah (opsional)</flux:subheading>

            <flux:input 
                wire:model="facebook_url" 
                label="Facebook" 
                type="url" 
                placeholder="https://facebook.com/namaakun"
            />

            <flux:input 
                wire:model="instagram_url" 
                label="Instagram" 
                type="url" 
                placeholder="https://instagram.com/namaakun"
            />

            <flux:input 
                wire:model="youtube_url" 
                label="YouTube" 
                type="url" 
                placeholder="https://youtube.com/@namaakun"
            />

            <flux:input 
                wire:model="twitter_url" 
                label="Twitter/X" 
                type="url" 
                placeholder="https://twitter.com/namaakun"
            />
        </div>

        <flux:separator />

        {{-- Lokasi --}}
        <div class="space-y-4">
            <flux:heading size="lg">Lokasi</flux:heading>
            <flux:subheading class="mb-4">Koordinat lokasi untuk Google Maps (opsional)</flux:subheading>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input 
                    wire:model="latitude" 
                    label="Latitude" 
                    type="text" 
                    placeholder="Contoh: -6.200000"
                />

                <flux:input 
                    wire:model="longitude" 
                    label="Longitude" 
                    type="text" 
                    placeholder="Contoh: 106.816666"
                />
            </div>

            <flux:text class="mt-2 text-sm">
                Tip: Anda bisa mendapatkan koordinat dari Google Maps dengan klik kanan pada lokasi dan pilih koordinat.
            </flux:text>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-4 pt-6">
            <flux:button 
                variant="primary" 
                type="submit" 
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">Simpan Profil</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </flux:button>
        </div>
    </form>
</div>
