<?php

use App\Models\User;
use App\Models\Level;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $role_filter = '';

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
        $this->dispatch('open-user-modal');
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
        
        $this->dispatch('open-user-modal');
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
        $this->dispatch('close-modal', 'user-modal');
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
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div>
            <flux:heading size="xl">Manajemen Pengguna</flux:heading>
            <flux:subheading>Kelola akun pengguna, role, dan akses sistem.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="createNew">Tambah User</flux:button>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center justify-between">
        <div class="flex gap-2 w-full md:w-auto">
            <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Cari user..." class="w-full md:w-64" />
            <flux:select wire:model.live="role_filter" placeholder="Semua Role" class="w-full md:w-48">
                <option value="">Semua Role</option>
                @foreach($this->roles as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Nama & Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">No. HP (WA)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Level</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">
                            <span class="capitalize">{{ $this->roles[$user->role] ?? $user->role }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">
                            {{ $user->managedLevel->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <flux:badge variant="{{ $user->is_active ? 'success' : 'danger' }}" size="sm">
                                {{ $user->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $user->id }})" />
                            @if($user->id !== auth()->id())
                                <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:confirm="Hapus user ini?" wire:click="delete({{ $user->id }})" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <flux:modal name="user-modal" class="max-w-md" @open-user-modal.window="$flux.modal('user-modal').show()" x-on:user-saved.window="$flux.modal('user-modal').close()">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Edit User' : 'Tambah User Baru' }}</flux:heading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:input wire:model="name" label="Nama Lengkap" required />
                <flux:input wire:model="email" type="email" label="Email Address" required />
                <flux:input wire:model="phone" type="tel" label="No. HP / WhatsApp" placeholder="08xxxxxxxx" />
                
                <flux:select wire:model.live="role" label="Role / Peran">
                    @foreach($this->roles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                @if(in_array($role, ['bendahara', 'kepsek']))
                    <flux:select wire:model="managed_level_id" label="Kelola Jenjang" placeholder="Pilih Jenjang">
                        <option value="">Pilih Jenjang</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </flux:select>
                @endif
                
                <flux:input wire:model="password" type="password" label="{{ $editing ? 'Password (Kosongkan jika tidak diubah)' : 'Password' }}" :required="!$editing" />
                
                <flux:switch wire:model="is_active" label="Status Aktif" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('user-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
