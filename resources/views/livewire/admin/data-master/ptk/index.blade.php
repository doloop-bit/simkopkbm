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

use Mary\Traits\Toast;

new #[Layout('components.admin.layouts.app')] class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public bool $ptkModal = false;
    
    // User fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'guru'; // guru, staf
    
    // Profile fields
    public string $nip = '';
    public string $phone = '';
    public string $address = '';
    
    // Teacher specific
    public string $education_level = '';
    
    // Staff specific
    public string $position = ''; // Kepala Sekolah, Kepala PKBM, Admin
    public ?int $level_id = null;
    public string $department = '';

    public ?User $editingUser = null;

    public function rules(): array
    {
        $profileableId = $this->editingUser?->profile?->profileable_id;
        
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                Rule::unique('users', 'email')->ignore($this->editingUser?->id)
            ],
            'password' => $this->editingUser ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:guru,staf',
            'nip' => [
                'nullable',
                'string',
                'max:20',
                $this->role === 'guru' 
                    ? Rule::unique('teacher_profiles', 'nip')->ignore($profileableId)
                    : Rule::unique('staff_profiles', 'nip')->ignore($profileableId)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'education_level' => 'required_if:role,guru|nullable|string',
            'position' => 'required_if:role,staf|nullable|string',
            'level_id' => 'nullable|exists:levels,id',
            'department' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $profileData = [
                'nip' => $this->nip ?: null,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
            ];

            if ($this->editingUser) {
                // Update User
                $this->editingUser->update([
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                ]);

                if ($this->password) {
                    $this->editingUser->update(['password' => Hash::make($this->password)]);
                }

                $profile = $this->editingUser->profile;
                $profileable = $profile->profileable;

                // Handle Role Change (Complex case)
                if (($this->role === 'guru' && !($profileable instanceof TeacherProfile)) ||
                    ($this->role === 'staf' && !($profileable instanceof StaffProfile))) {
                    
                    // Delete old profileable
                    $profileable->delete();

                    // Create new profileable
                    if ($this->role === 'guru') {
                        $newProfileable = TeacherProfile::create(array_merge($profileData, [
                            'education_level' => $this->education_level,
                        ]));
                    } else {
                        $newProfileable = StaffProfile::create(array_merge($profileData, [
                            'position' => $this->position,
                            'level_id' => $this->level_id,
                            'department' => $this->department,
                        ]));
                    }

                    $profile->update([
                        'profileable_id' => $newProfileable->id,
                        'profileable_type' => get_class($newProfileable),
                    ]);
                } else {
                    // Update existing
                    if ($this->role === 'guru') {
                        $profileable->update(array_merge($profileData, [
                            'education_level' => $this->education_level,
                        ]));
                    } else {
                        $profileable->update(array_merge($profileData, [
                            'position' => $this->position,
                            'level_id' => $this->level_id,
                            'department' => $this->department,
                        ]));
                    }
                }
                $this->success('Data PTK berhasil diperbarui.');
            } else {
                // Create User
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'role' => $this->role,
                ]);

                // Create Profileable
                if ($this->role === 'guru') {
                    $profileable = TeacherProfile::create(array_merge($profileData, [
                        'education_level' => $this->education_level,
                    ]));
                } else {
                    $profileable = StaffProfile::create(array_merge($profileData, [
                        'position' => $this->position,
                        'level_id' => $this->level_id,
                        'department' => $this->department,
                    ]));
                }

                // Create Profile
                Profile::create([
                    'user_id' => $user->id,
                    'profileable_id' => $profileable->id,
                    'profileable_type' => get_class($profileable),
                ]);
                
                $this->success('Data PTK berhasil ditambahkan.');
            }
        });

        $this->reset(['name', 'email', 'password', 'role', 'nip', 'phone', 'address', 'education_level', 'position', 'level_id', 'department', 'editingUser', 'ptkModal']);
    }

    public function create(): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'nip', 'phone', 'address', 'education_level', 'position', 'level_id', 'department', 'editingUser']);
        $this->resetValidation();
        $this->ptkModal = true;
    }

    public function edit(User $user): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'nip', 'phone', 'address', 'education_level', 'position', 'level_id', 'department', 'editingUser']);
        $this->resetValidation();
        
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;

        $profile = $user->profile;
        $profileable = $profile?->profileable;

        if ($profileable) {
            $this->nip = $profileable->nip ?? '';
            $this->phone = $profileable->phone ?? '';
            $this->address = $profileable->address ?? '';

            if ($profileable instanceof TeacherProfile) {
                $this->education_level = $profileable->education_level ?? '';
            } elseif ($profileable instanceof StaffProfile) {
                $this->position = $profileable->position ?? '';
                $this->level_id = $profileable->level_id;
                $this->department = $profileable->department ?? '';
            }
        }

        $this->ptkModal = true;
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $profile = $user->profile;
            if ($profile) {
                $profile->profileable?->delete();
                $profile->delete();
            }
            $user->delete();
        });
        $this->success('Data PTK berhasil dihapus.');
    }

    public function with(): array
    {
        $users = User::with([
            'profile.profileable' => function ($morph) {
                $morph->morphWith([
                    StaffProfile::class => ['level'],
                ]);
            }
        ])
            ->whereIn('role', ['guru', 'staf'])
            ->when($this->search, function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(10);

        return [
            'users' => $users,
            'levels' => Level::all(),
        ];
    }
}; ?>

<div class="p-6">
    <x-header title="Manajemen PTK" subtitle="Pendidik dan Tenaga Kependidikan (Guru & Staf).">
        <x-slot:actions>
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari ptk..." icon="o-magnifying-glass" class="w-64" clearable />
            <x-button label="Tambah PTK" icon="o-plus" wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <x-table :headers="[
            ['key' => 'name', 'label' => 'Nama / Email'],
            ['key' => 'role_label', 'label' => 'Role / Jabatan'],
            ['key' => 'contact', 'label' => 'Kontak'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right']
        ]" :rows="$users">
            @scope('cell_name', $user)
                <div class="flex flex-col">
                    <span class="font-bold">{{ $user->name }}</span>
                    <span class="text-xs opacity-50">{{ $user->email }}</span>
                </div>
            @endscope

            @scope('cell_role_label', $user)
                @php
                    $profileable = $user->profile?->profileable;
                    $position = $user->role === 'guru' ? 'Guru / Pendidik' : ($profileable?->position ?? 'Tenaga Kependidikan');
                    if($user->role === 'staf' && $profileable?->level) {
                        $position .= ' (' . $profileable->level->name . ')';
                    }
                @endphp
                <div class="flex flex-col gap-1">
                    <x-badge 
                        :label="strtoupper($user->role)" 
                        class="{{ $user->role === 'guru' ? 'badge-success' : 'badge-primary' }} badge-sm" 
                    />
                    <span class="text-[10px] uppercase opacity-60">{{ $position }}</span>
                </div>
            @endscope

            @scope('cell_contact', $user)
                @php $profileable = $user->profile?->profileable; @endphp
                <div class="flex flex-col text-sm">
                    <span>{{ $profileable->phone ?? '-' }}</span>
                    <span class="text-xs opacity-50 truncate max-w-[200px]">{{ $profileable->address ?? '-' }}</span>
                </div>
            @endscope

            @scope('cell_actions', $user)
                <div class="flex justify-end gap-1">
                    <x-button icon="o-pencil-square" wire:click="edit({{ $user->id }})" ghost sm tooltip="Edit" />
                    <x-button 
                        icon="o-trash" 
                        wire:confirm="Yakin ingin menghapus data ini?"
                        wire:click="delete({{ $user->id }})"
                        ghost sm class="text-error" 
                        tooltip="Hapus" 
                    />
                </div>
            @endscope
        </x-table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-card>

    <x-modal wire:model="ptkModal" class="backdrop-blur">
        <x-header :title="$editingUser ? 'Edit PTK' : 'Tambah PTK'" subtitle="Isi informasi akun dan data profil PTK." separator />

        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-sm opacity-70 italic">Akun & Identitas</div>
                    <x-input wire:model="name" label="Nama Lengkap" />
                    <x-input wire:model="email" label="Email" type="email" />
                    <x-input wire:model="password" label="Password {{ $editingUser ? '(Kosongkan jika tidak diubah)' : '' }}" type="password" />
                    <x-select 
                        wire:model.live="role" 
                        label="Status PTK" 
                        :options="[
                            ['id' => 'guru', 'name' => 'Pendidik (Guru)'],
                            ['id' => 'staf', 'name' => 'Tenaga Kependidikan (Staf)'],
                        ]" 
                    />
                    <x-input wire:model="nip" label="NIP / No. Pegawai" placeholder="Optional" />
                </div>

                <div class="space-y-4">
                    <div class="font-bold border-b pb-1 text-sm opacity-70 italic">Detail Profil</div>
                    <x-input wire:model="phone" label="No. Telepon" icon="o-phone" />
                    
                    @if($role === 'guru')
                        <x-input wire:model="education_level" label="Pendidikan Terakhir" />
                    @else
                        <x-select 
                            wire:model.live="position" 
                            label="Jabatan" 
                            placeholder="Pilih Jabatan"
                            :options="[
                                ['id' => 'Kepala PKBM', 'name' => 'Kepala PKBM'],
                                ['id' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah (Jenjang)'],
                                ['id' => 'Bendahara', 'name' => 'Bendahara'],
                                ['id' => 'Administrasi', 'name' => 'Administrasi / Operator'],
                                ['id' => 'Lainnya', 'name' => 'Lainnya'],
                            ]"
                        />

                        @if($position === 'Kepala Sekolah')
                            <x-select 
                                wire:model="level_id" 
                                label="Jenjang" 
                                placeholder="Pilih Jenjang"
                                :options="$levels"
                            />
                        @endif

                        <x-input wire:model="department" label="Bagian / Departemen" />
                    @endif

                    <x-textarea wire:model="address" label="Alamat Lengkap" rows="3" />
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('ptkModal', false)" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
