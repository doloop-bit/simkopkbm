<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Profile;
use App\Models\TeacherProfile;
use App\Models\StaffProfile;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public bool $ptkModal = false;
    public ?User $editingUser = null;

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'guru';
    public $nip = '';
    public $phone = '';
    public $education_level = '';
    public $position = '';
    public $level_id = '';
    public $department = '';
    public $address = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingUser', 'name', 'email', 'password', 'role', 'nip', 'phone', 'education_level', 'position', 'level_id', 'department', 'address']);
        $this->role = 'guru';
        $this->resetValidation();
        $this->ptkModal = true;
    }

    public function edit(User $user): void
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';

        $profile = $user->profile?->profileable;
        if ($profile) {
            $this->nip = $profile->nip ?? '';
            $this->phone = $profile->phone ?? '';
            $this->address = $profile->address ?? '';
            
            if ($user->role === 'guru') {
                $this->education_level = $profile->education_level ?? '';
            } else {
                $this->position = $profile->position ?? '';
                $this->level_id = $profile->level_id ?? '';
                $this->department = $profile->department ?? '';
            }
        }

        $this->ptkModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingUser->id ?? null)],
            'role' => 'required|in:guru,staf,admin,yayasan,bendahara,kepsek',
            'nip' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ];

        if (!$this->editingUser) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        if ($this->role === 'guru') {
            $rules['education_level'] = 'nullable|string';
        } else {
            $rules['position'] = 'nullable|string';
            $rules['level_id'] = 'nullable|exists:levels,id';
            $rules['department'] = 'nullable|string';
        }

        $this->validate($rules);

        DB::transaction(function () {
            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ];

            if ($this->password) {
                $userData['password'] = Hash::make($this->password);
            }

            if ($this->editingUser) {
                $this->editingUser->update($userData);
                $user = $this->editingUser;
            } else {
                $user = User::create($userData);
            }

            $profileData = [
                'nip' => $this->nip,
                'phone' => $this->phone,
                'address' => $this->address,
            ];

            $profileType = $this->role === 'guru' ? TeacherProfile::class : StaffProfile::class;
            
            if ($this->role === 'guru') {
                $profileData['education_level'] = $this->education_level;
            } else {
                $profileData['position'] = $this->position;
                $profileData['level_id'] = $this->level_id ?: null;
                $profileData['department'] = $this->department;
            }

            $currentProfile = $user->profile;
            
            if ($currentProfile && $currentProfile->profileable_type === $profileType) {
                $currentProfile->profileable->update($profileData);
            } else {
                if ($currentProfile) {
                    $currentProfile->profileable->delete();
                    $currentProfile->delete();
                }

                $profileable = $profileType::create($profileData);
                $user->profiles()->create([
                    'profileable_id' => $profileable->id,
                    'profileable_type' => $profileType,
                ]);
            }
        });

        $this->ptkModal = false;
        session()->flash('success', __('Data PTK berhasil disimpan.'));
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($user->profile) {
                $user->profile->profileable->delete();
                $user->profile->delete();
            }
            $user->delete();
        });
        session()->flash('success', __('Data PTK berhasil dihapus.'));
    }

    public function with(): array
    {
        return [
            'users' => User::query()
                ->whereNotIn('role', ['siswa'])
                ->with(['profile.profileable'])
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
            'levels' => Level::orderBy('name')->get()->map(fn($l) => ['id' => $l->id, 'name' => $l->name]),
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Manajemen PTK')" :subtitle="__('Pendidik dan Tenaga Kependidikan (Guru & Staf).')">
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari ptk...')" icon="o-magnifying-glass" sm class="w-64" clearable />
                <x-ui.button :label="__('Tambah PTK')" icon="o-plus" wire:click="create" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-ui.header>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama / Email')],
                ['key' => 'role_label', 'label' => __('Role / Jabatan')],
                ['key' => 'contact', 'label' => __('Kontak')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right']
            ]" 
            :rows="$users"
        >
            @scope('cell_name', $user)
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</span>
                    <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ $user->email }}</span>
                </div>
            @endscope

            @scope('cell_role_label', $user)
                @php
                    $profileable = $user->profile?->profileable;
                    $position = match($user->role) {
                        'guru' => __('Guru / Pendidik'),
                        'staf' => $profileable?->position ?? __('Tenaga Kependidikan'),
                        'admin' => __('Administrator'),
                        'kepsek' => __('Kepala Sekolah'),
                        'bendahara' => __('Bendahara'),
                        'yayasan' => __('Yayasan'),
                        default => ucfirst($user->role)
                    };
                    if ($profileable?->level) {
                        $position .= ' (' . $profileable->level->name . ')';
                    } elseif ($user->managed_level_id) {
                        $levelName = \App\Models\Level::find($user->managed_level_id)?->name;
                        if ($levelName) $position .= ' (' . $levelName . ')';
                    }
                @endphp
                <div class="flex flex-col gap-1">
                    <x-ui.badge 
                        :label="strtoupper($user->role)" 
                        class="{{ $user->role === 'guru' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }} text-[10px] font-black w-fit" 
                    />
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-tight">{{ $position }}</span>
                </div>
            @endscope

            @scope('cell_contact', $user)
                @php $profileable = $user->profile?->profileable; @endphp
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 font-mono italic">{{ $profileable->phone ?? '-' }}</span>
                    <span class="text-[10px] text-slate-400 truncate max-w-[200px]">{{ $profileable->address ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $user->id }})" ghost sm />
                    <x-ui.button 
                        icon="o-trash" 
                        class="text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10" 
                        wire:confirm="{{ __('Yakin ingin menghapus data ini?') }}"
                        wire:click="delete({{ $user->id }})"
                        ghost sm 
                    />
                </div>
            @endscope
        </x-ui.table>

        @if(collect($users)->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada data PTK yang ditemukan.') }}
            </div>
        @endif
    </x-ui.card>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <x-ui.modal wire:model="ptkModal" persistent maxWidth="max-w-4xl">
        <x-ui.header :title="$editingUser ? __('Edit PTK') : __('Tambah PTK')" :subtitle="__('Isi informasi akun dan data profil PTK.')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Akun & Identitas') }}</div>
                    <x-ui.input wire:model="name" :label="__('Nama Lengkap')" required />
                    <x-ui.input wire:model="email" :label="__('Email')" type="email" required />
                    <x-ui.input wire:model="password" :label="$editingUser ? __('Password (Kosongkan jika tidak diubah)') : __('Password')" type="password" :required="!$editingUser" />
                    <x-ui.select 
                        wire:model.live="role" 
                        :label="__('Status PTK / Role')" 
                        :options="[
                            ['id' => 'guru', 'name' => __('Pendidik (Guru)')],
                            ['id' => 'staf', 'name' => __('Tenaga Kependidikan (Staf)')],
                            ['id' => 'admin', 'name' => __('Administrator')],
                            ['id' => 'kepsek', 'name' => __('Kepala Sekolah')],
                            ['id' => 'bendahara', 'name' => __('Bendahara')],
                            ['id' => 'yayasan', 'name' => __('Yayasan')],
                        ]" 
                        required
                    />
                    <x-ui.input wire:model="nip" :label="__('NIP / No. Pegawai')" :placeholder="__('Optional')" />
                </div>

                <div class="space-y-5">
                    <div class="text-[11px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">{{ __('Detail Profil') }}</div>
                    <x-ui.input wire:model="phone" :label="__('No. Telepon')" icon="o-phone" />
                    
                    @if($role === 'guru')
                        <x-ui.input wire:model="education_level" :label="__('Pendidikan Terakhir')" />
                    @else
                        <x-ui.select 
                            wire:model.live="position" 
                            :label="__('Jabatan')" 
                            :placeholder="__('Pilih Jabatan')"
                            :options="[
                                ['id' => 'Kepala PKBM', 'name' => __('Kepala PKBM')],
                                ['id' => 'Kepala Sekolah', 'name' => __('Kepala Sekolah (Jenjang)')],
                                ['id' => 'Bendahara', 'name' => __('Bendahara')],
                                ['id' => 'Administrasi', 'name' => __('Administrasi / Operator')],
                                ['id' => 'Lainnya', 'name' => __('Lainnya')],
                            ]"
                        />

                        @if($position === 'Kepala Sekolah')
                            <x-ui.select 
                                wire:model="level_id" 
                                :label="__('Jenjang')" 
                                :placeholder="__('Pilih Jenjang')"
                                :options="$levels"
                            />
                        @endif

                        <x-ui.input wire:model="department" :label="__('Bagian / Departemen')" />
                    @endif

                    <x-ui.textarea wire:model="address" :label="__('Alamat Lengkap')" rows="3" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
