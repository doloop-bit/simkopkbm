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
    public array $history = [];
    public $historyImages = []; // Temp storage for new uploads
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
            $this->history = is_array($this->profile->history) ? $this->profile->history : [];
            $this->operating_hours = $this->profile->operating_hours ?? '';
            $this->facebook_url = $this->profile->facebook_url ?? '';
            $this->instagram_url = $this->profile->instagram_url ?? '';
            $this->youtube_url = $this->profile->youtube_url ?? '';
            $this->twitter_url = $this->profile->twitter_url ?? '';
            $this->latitude = $this->profile->latitude ?? '';
            $this->longitude = $this->profile->longitude ?? '';
            $this->currentLogoPath = $this->profile->logo_path;
        }

        if (empty($this->history)) {
            $this->addTimelineItem();
        }
    }

    public function addTimelineItem(): void
    {
        $this->history[] = [
            'year' => '',
            'title' => '',
            'description' => '',
            'image_path' => null
        ];
    }

    public function removeTimelineItem(int $index): void
    {
        if (!empty($this->history[$index]['image_path'])) {
            Storage::disk('public')->delete($this->history[$index]['image_path']);
        }

        unset($this->history[$index]);
        unset($this->historyImages[$index]);
        
        $this->history = array_values($this->history);
        $this->historyImages = array_values($this->historyImages);
        
        if (empty($this->history)) {
            $this->addTimelineItem();
        }
    }

    public function removeTimelineImage(int $index): void
    {
        if (!empty($this->history[$index]['image_path'])) {
            Storage::disk('public')->delete($this->history[$index]['image_path']);
            $this->history[$index]['image_path'] = null;
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
            'history' => 'nullable|array',
            'history.*.year' => 'required|string|max:10',
            'history.*.title' => 'required|string|max:255',
            'history.*.description' => 'required|string',
            'historyImages.*' => 'nullable|image|max:2048',
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

    public function save(): void
    {
        $this->validate();

        if (!$this->profile) {
            $this->profile = new SchoolProfile();
        }

        foreach ($this->historyImages as $index => $file) {
            if ($file) {
                if (!empty($this->history[$index]['image_path'])) {
                    Storage::disk('public')->delete($this->history[$index]['image_path']);
                }
                $path = $file->store('school-profile/history', 'public');
                $this->history[$index]['image_path'] = $path;
            }
        }

        if ($this->logo) {
            if ($this->profile->logo_path) {
                Storage::disk('public')->delete($this->profile->logo_path);
            }
            $path = $this->logo->store('school-profile', 'public');
            $this->profile->logo_path = $path;
            $this->currentLogoPath = $path;
        }

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
        $this->historyImages = [];
        $this->logo = null;

        app(CacheService::class)->clearSchoolProfileCache();
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
                <x-ui.input wire:model="name" :label="__('Nama Lengkap Sekolah / Lembaga')" type="text" required class="font-semibold text-lg" />
                <x-ui.textarea wire:model="address" :label="__('Alamat Domisili Lengkap')" rows="3" required />

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-ui.input wire:model="phone" :label="__('Nomor Telepon / WhatsApp')" type="text" required icon="o-phone" />
                    <x-ui.input wire:model="email" :label="__('Alamat Email Resmi')" type="email" required icon="o-envelope" />
                </div>

                <x-ui.input wire:model="operating_hours" :label="__('Jam Layanan Operasional')" type="text" icon="o-clock" />
            </div>
        </x-ui.card>

        {{-- Logo Sekolah --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Identitas Visual (Logo)') }}</h3>
            </div>
            <div class="p-8 space-y-8">
                @if ($currentLogoPath)
                    <div class="flex items-center gap-8 p-6 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800">
                        <img src="{{ Storage::url($currentLogoPath) }}" alt="Logo" class="h-32 w-32 rounded-2xl object-contain bg-white shadow-xl">
                        <x-ui.button wire:click="removeLogo" :label="__('Hapus Logo')" icon="o-trash" class="btn-ghost btn-xs text-rose-500" wire:confirm="Hapus logo?" />
                    </div>
                @endif
                <x-ui.file wire:model="logo" :label="__('Unggah Logo Baru')" accept="image/*" />
            </div>
        </x-ui.card>

        {{-- Visi & Misi --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Budaya & Filosofi (Visi & Misi)') }}</h3>
            </div>
            <div class="p-8 space-y-6">
                <x-ui.textarea wire:model="vision" :label="__('Visi')" rows="3" required class="font-semibold text-sm text-indigo-600" />
                <x-ui.textarea wire:model="mission" :label="__('Misi')" rows="6" required />
            </div>
        </x-ui.card>

        {{-- Sejarah Timeline --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Rekam Jejak & Sejarah (Timeline)') }}</h3>
                </div>
                <x-ui.button label="Tambah Peristiwa" icon="o-plus" class="btn-sm btn-ghost text-primary" wire:click="addTimelineItem" />
            </div>
            <div class="p-8 space-y-12">
                @foreach($history as $index => $item)
                    <div class="relative pl-8 border-l-2 border-slate-100 dark:border-slate-800 space-y-6 pb-8 last:pb-0">
                        <div class="absolute -left-[9px] top-0 size-4 rounded-full bg-white border-4 border-primary shadow-sm"></div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-1">
                                <x-ui.input wire:model="history.{{ $index }}.year" :label="__('Tahun')" required />
                            </div>
                            <div class="md:col-span-3">
                                <div class="flex gap-4">
                                    <x-ui.input wire:model="history.{{ $index }}.title" :label="__('Judul')" required class="flex-1" />
                                    <x-ui.button icon="o-trash" class="btn-ghost btn-sm text-rose-500 mt-8" wire:click="removeTimelineItem({{ $index }})" wire:confirm="Hapus butir ini?" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="md:col-span-2">
                                <x-ui.textarea wire:model="history.{{ $index }}.description" :label="__('Deskripsi')" rows="4" required />
                            </div>
                            <div class="md:col-span-1">
                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Foto Peristiwa') }}</label>
                                    
                                    @if(!empty($item['image_path']))
                                        <div class="relative group">
                                            <img src="{{ Storage::url($item['image_path']) }}" class="w-full h-32 object-cover rounded-xl border">
                                            <x-ui.button icon="o-x-mark" class="btn-circle btn-xs absolute -top-2 -right-2 bg-rose-500 text-white" wire:click="removeTimelineImage({{ $index }})" />
                                        </div>
                                    @endif

                                    <x-ui.file wire:model="historyImages.{{ $index }}" accept="image/*">
                                        <div class="text-[10px] text-slate-400 mt-1">{{ __('Klik untuk ganti foto') }}</div>
                                    </x-ui.file>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Media Sosial --}}
        <x-ui.card shadow>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-xs">{{ __('Media Sosial') }}</h3>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.input wire:model="facebook_url" label="Facebook" type="url" icon="o-link" />
                    <x-ui.input wire:model="instagram_url" label="Instagram" type="url" icon="o-link" />
                    <x-ui.input wire:model="youtube_url" label="YouTube" type="url" icon="o-link" />
                    <x-ui.input wire:model="twitter_url" label="Twitter / X" type="url" icon="o-link" />
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
                    <x-ui.input wire:model="latitude" label="Latitude" type="text" class="font-mono text-xs" />
                    <x-ui.input wire:model="longitude" label="Longitude" type="text" class="font-mono text-xs" />
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-4 py-12 border-t border-slate-100 dark:border-slate-800">
            <x-ui.button :label="__('Simpan Seluruh Profil')" class="btn-primary px-12 py-4" type="submit" spinner="save" />
        </div>
    </form>
</div>
