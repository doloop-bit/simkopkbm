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

<div class="flex flex-col gap-6">
    <x-header title="Manajemen Pengguna" subtitle="Kelola akun pengguna, role, dan akses sistem." separator>
        <x-slot:actions>
             <x-button label="Tambah User" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-header>

    <div class="flex flex-col md:flex-row gap-4 mb-2 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <x-input wire:model.live="search" icon="o-magnifying-glass" placeholder="Cari user..." class="w-full md:w-64" />
            <x-select wire:model.live="role_filter" placeholder="Semua Role" class="w-full md:w-48" :options="collect($this->roles)->map(fn($v, $k) => ['id' => $k, 'name' => $v])->values()->toArray()" />
        </div>
    </div>

    <div class="overflow-x-auto border rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
        <table class="table">
            <thead>
                <tr>
                    <th class="bg-base-200">Nama & Email</th>
                    <th class="bg-base-200">Role</th>
                    <th class="bg-base-200 text-center">No. HP</th>
                    <th class="bg-base-200 text-center">Jenjang</th>
                    <th class="bg-base-200 text-center">Status</th>
                    <th class="bg-base-200 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="hover">
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-zinc-900 dark:text-white">{{ $user->name }}</span>
                                <span class="text-xs opacity-60">{{ $user->email }}</span>
                            </div>
                        </td>
                        <td class="capitalize opacity-70">
                            {{ $this->roles[$user->role] ?? $user->role }}
                        </td>
                        <td class="text-center opacity-70 whitespace-nowrap">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="text-center opacity-70">
                            {{ $user->managedLevel->name ?? '-' }}
                        </td>
                        <td class="text-center">
                            <x-badge :value="$user->is_active ? 'Aktif' : 'Non-Aktif'" class="{{ $user->is_active ? 'badge-success' : 'badge-error' }} badge-sm" />
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <x-button icon="o-pencil-square" wire:click="edit({{ $user->id }})" ghost sm />
                                @if($user->id !== auth()->id())
                                    <x-button icon="o-trash" class="text-error" wire:confirm="Hapus user ini?" wire:click="delete({{ $user->id }})" ghost sm />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <x-modal wire:model="userModal" class="backdrop-blur">
        <x-header :title="$editing ? 'Edit User' : 'Tambah User Baru'" separator />
        
        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-4">
                <x-input wire:model="name" label="Nama Lengkap" required />
                <x-input wire:model="email" type="email" label="Email Address" required />
                <x-input wire:model="phone" type="tel" label="No. HP / WhatsApp" placeholder="08xxxxxxxx" />
                
                <x-select wire:model.live="role" label="Role / Peran" :options="collect($this->roles)->map(fn($v, $k) => ['id' => $k, 'name' => $v])->values()->toArray()" />

                @if(in_array($role, ['bendahara', 'kepsek']))
                    <x-select wire:model="managed_level_id" label="Kelola Jenjang" placeholder="Pilih Jenjang" :options="$levels" />
                @endif
                
                <x-input wire:model="password" type="password" label="{{ $editing ? 'Password (Kosongkan jika tidak diubah)' : 'Password' }}" :required="!$editing" />
                
                <x-checkbox wire:model="is_active" label="Status Aktif" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" @click="$set('userModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
