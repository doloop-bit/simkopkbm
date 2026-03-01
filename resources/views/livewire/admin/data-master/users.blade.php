<?php

use App\Models\User;
use App\Models\Level;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $role_filter = '';
    public bool $userModal = false;

    public $name = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $role = 'user';
    public $managed_level_id = '';
    public $is_active = true;

    public ?User $editing = null;

    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->role_filter, fn($q) => $q->where('role', $this->role_filter))
                ->latest()
                ->paginate(10),
            'levels' => Level::orderBy('name')->get(),
        ];
    }

    public function createNew(): void
    {
        $this->reset(['name', 'email', 'phone', 'password', 'role', 'managed_level_id', 'is_active', 'editing']);
        $this->role = 'user';
        $this->is_active = true;
        $this->resetValidation();
        $this->userModal = true;
    }

    public function edit(User $user): void
    {
        $this->editing = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->role = $user->role;
        $this->managed_level_id = $user->managed_level_id;
        $this->is_active = $user->is_active;
        $this->password = ''; // Don't fill password
        
        $this->userModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editing->id ?? null)],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string',
            'managed_level_id' => 'nullable|exists:levels,id',
            'is_active' => 'boolean',
        ];

        if (!$this->editing) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'managed_level_id' => in_array($this->role, ['bendahara', 'kepsek']) ? $this->managed_level_id : null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            User::create($data);
        }

        $this->dispatch('user-saved');
        $this->userModal = false;
        $this->reset(['name', 'email', 'phone', 'password', 'role', 'managed_level_id', 'is_active', 'editing']);
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            return; // Prevent self-delete
        }
        $user->delete();
    }
    
    public function getRolesProperty()
    {
        return [
            'admin' => 'Administrator',
            'yayasan' => 'Yayasan',
            'kepsek' => 'Kepala Sekolah',
            'bendahara' => 'Bendahara',
            'guru' => 'Guru',
            'staff' => 'Staff',
            'siswa' => 'Siswa',
            'user' => 'User Umum',
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Manajemen Pengguna')" :subtitle="__('Kelola akun pengguna, role, dan akses sistem.')" separator>
        <x-slot:actions>
             <x-ui.button :label="__('Tambah User')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <x-ui.input wire:model.live="search" icon="o-magnifying-glass" :placeholder="__('Cari user...')" class="w-full md:w-64" sm />
            <x-ui.select wire:model.live="role_filter" :placeholder="__('Semua Role')" class="w-full md:w-48" :options="collect($this->roles)->map(fn($v, $k) => ['id' => $k, 'name' => $v])->values()->toArray()" sm />
        </div>
    </div>

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card shadow padding="false">
        <x-ui.table 
            :headers="[
                ['key' => 'name', 'label' => __('Nama & Email')],
                ['key' => 'role', 'label' => __('Role')],
                ['key' => 'phone', 'label' => __('No. HP')],
                ['key' => 'managed_level_id', 'label' => __('Jenjang')],
                ['key' => 'is_active', 'label' => __('Status')],
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

            @scope('cell_role', $user)
                <x-ui.badge :label="$this->roles[$user->role] ?? $user->role" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-bold" />
            @endscope

            @scope('cell_phone', $user)
                <span class="text-sm text-slate-600 dark:text-slate-400 font-mono italic">
                    {{ $user->phone ?? '-' }}
                </span>
            @endscope

            @scope('cell_managed_level_id', $user)
                <span class="text-xs text-slate-500 font-medium">
                    {{ $user->managedLevel->name ?? '-' }}
                </span>
            @endscope

            @scope('cell_is_active', $user)
                @if($user->is_active)
                    <x-ui.badge :label="__('Aktif')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black" />
                @else
                    <x-ui.badge :label="__('Non-Aktif')" class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-[10px] font-black" />
                @endif
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $user->id }})" ghost sm />
                    @if($user->id !== auth()->id())
                        <x-ui.button 
                            icon="o-trash" 
                            class="text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10" 
                            wire:confirm="{{ __('Hapus user ini?') }}" 
                            wire:click="delete({{ $user->id }})" 
                            ghost sm 
                        />
                    @endif
                </div>
            @endscope
        </x-ui.table>

        @if(collect($users)->isEmpty())
            <div class="py-12 text-center text-slate-400 italic text-sm">
                {{ __('Belum ada data pengguna.') }}
            </div>
        @endif
    </x-ui.card>
    
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <x-ui.modal wire:model="userModal" persistent>
        <x-ui.header :title="$editing ? __('Edit User') : __('Tambah User Baru')" separator />
        
        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="name" :label="__('Nama Lengkap')" required />
            <x-ui.input wire:model="email" type="email" :label="__('Email Address')" required />
            <x-ui.input wire:model="phone" type="tel" :label="__('No. HP / WhatsApp')" :placeholder="__('08xxxxxxxx')" />
            
            <x-ui.select wire:model.live="role" :label="__('Role / Peran')" :options="collect($this->roles)->map(fn($v, $k) => ['id' => $k, 'name' => $v])->values()->toArray()" />

            @if(in_array($role, ['bendahara', 'kepsek']))
                <x-ui.select wire:model="managed_level_id" :label="__('Kelola Jenjang')" :placeholder="__('Pilih Jenjang')" :options="$levels" />
            @endif
            
            <x-ui.input wire:model="password" type="password" :label="$editing ? __('Password (Kosongkan jika tidak diubah)') : __('Password')" :required="!$editing" />
            
            <x-ui.checkbox wire:model="is_active" :label="__('Status Aktif')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
