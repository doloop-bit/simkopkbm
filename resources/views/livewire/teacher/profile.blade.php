<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.teacher.layouts.app')] class extends Component {
    public $name = '';
    public $email = '';
    public $password = '';
    public $nip = '';
    public $phone = '';
    public $education_level = '';
    public $address = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        
        $profile = $user->profile?->profileable;
        if ($profile) {
            $this->nip = $profile->nip ?? '';
            $this->phone = $profile->phone ?? '';
            $this->education_level = $profile->education_level ?? '';
            $this->address = $profile->address ?? '';
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'nip' => 'nullable|string',
            'phone' => 'nullable|string',
            'education_level' => 'nullable|string',
            'address' => 'nullable|string',
        ];

        $this->validate($rules);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        $user->update($userData);

        $profileData = [
            'nip' => $this->nip,
            'phone' => $this->phone,
            'education_level' => $this->education_level,
            'address' => $this->address,
        ];

        $currentProfile = $user->profile;
        if ($currentProfile && $currentProfile->profileable_type === \App\Models\TeacherProfile::class) {
            $currentProfile->profileable->update($profileData);
        } else {
            if ($currentProfile) {
                $currentProfile->profileable->delete();
                $currentProfile->delete();
            }
            $profileable = \App\Models\TeacherProfile::create($profileData);
            $user->profiles()->create([
                'profileable_id' => $profileable->id,
                'profileable_type' => \App\Models\TeacherProfile::class,
            ]);
        }

        session()->flash('success', __('Profil berhasil diperbarui.'));
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white pb-24 md:pb-6">
    <x-ui.header :title="__('Profil Saya')" :subtitle="__('Kelola informasi akun dan biodata Anda.')" separator />

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100 mb-6" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Akun & Identitas') }}</div>
                    <x-ui.input wire:model="name" :label="__('Nama Lengkap')" required />
                    <x-ui.input wire:model="email" :label="__('Email')" type="email" required />
                    <x-ui.input wire:model="password" :label="__('Password (Kosongkan jika tidak diubah)')" type="password" />
                    <x-ui.input wire:model="nip" :label="__('NIP / No. Pegawai')" :placeholder="__('Opsional')" />
                </div>

                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Detail Profil') }}</div>
                    <x-ui.input wire:model="phone" :label="__('No. Telepon')" icon="o-phone" />
                    <x-ui.input wire:model="education_level" :label="__('Pendidikan Terakhir')" />
                    <x-ui.textarea wire:model="address" :label="__('Alamat Lengkap')" rows="3" />
                </div>
            </div>

            <div class="flex justify-start gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Simpan Perubahan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.card>
</div>
