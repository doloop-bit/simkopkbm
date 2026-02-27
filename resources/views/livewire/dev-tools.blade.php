<?php

use Livewire\Component;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Profile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $secret = '';

    public function deleteAllStudents()
    {
        // Pengecekan keamanan sederhana
        if ($this->secret !== 'atiati') {
            $this->error('Password salah!');
            return;
        }

        DB::transaction(function () {
            // Nonaktifkan constraint
            Schema::disableForeignKeyConstraints();

            // 1. Ambil ID StudentProfile yang ada di tabel profiles
            // 1. Hapus data terkait siswa secara berurutan untuk memastikan relasi terhapus dengan benar
            // Hapus User dengan role siswa
            $students = User::where('role', 'siswa')->get();

            foreach ($students as $student) {
                if ($student->latestProfile) {
                    $profile = $student->latestProfile->profileable;
                    // Hapus foto jika ada
                    if ($profile && $profile->photo) {
                        Storage::disk('public')->delete($profile->photo);
                    }

                    // Hapus StudentProfile
                    if ($profile) {
                        $profile->delete();
                    }

                    // Hapus Profile (morph)
                    $student->latestProfile->delete();
                }
                // Hapus User
                $student->delete();
            }

            // Hapus data periodik (bisa truncate karena tidak ada relasi langsung ke user/profile yang perlu event)
            \App\Models\StudentPeriodicRecord::truncate();

            Schema::enableForeignKeyConstraints();
        });

        $this->success('Seluruh data siswa (User, Profil, & Data Periodik) berhasil dihapus bersih.');

        $this->reset('secret');
    }

    // Nanti Anda bisa copy paste method di atas dan ganti nama fungsinya
    // jika ingin membuat tombol delete tabel/data yang lain
    public function deleteOtherData()
    {
        // ... tambah script hapus data tabel lain
    }
};
?>

<div class="h-screen flex items-center justify-center p-6">
    <x-card shadow class="max-w-xl w-full">
        <x-header title="🚀 Demo Tools" subtitle="Gunakan fitur ini hanya selama masa testing untuk reset data secara cepat." separator>
            <x-slot:actions>
                <x-badge label="HATI-HATI!" class="badge-error font-black shadow-sm" />
            </x-slot:actions>
        </x-header>

        <div class="flex flex-col gap-6">
            <x-input 
                label="Password Rahasia" 
                type="password" 
                wire:model="secret" 
                placeholder="Masukkan password testing" 
                icon="o-key"
                password-reveal
            />

            <div class="pt-4 border-t border-base-300 flex flex-col gap-3">
                <x-button 
                    label="Kosongkan Tabel Siswa (Truncate)" 
                    icon="o-trash" 
                    class="btn-error btn-outline" 
                    wire:click="deleteAllStudents"
                    wire:confirm="Yakin ingin MENGHAPUS SEMUA data Siswa hasil import?"
                    spinner
                />
            </div>
        </div>
    </x-card>
</div>
