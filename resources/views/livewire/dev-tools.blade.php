<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Profile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public string $secret = '';

    public function deleteAllStudents()
    {
        // Pengecekan keamanan sederhana
        if ($this->secret !== 'atiati') {
            $this->addError('secret', 'Password salah!');
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

        session()->flash('success', 'Seluruh data siswa (User, Profil, & Data Periodik) berhasil dihapus bersih.');

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

<div
    class="max-w-xl p-6 mx-auto mt-10 space-y-6 bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-zinc-900 dark:border-zinc-800">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">🚀 Demo Tools - Hati-hati!</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gunakan fitur ini hanya selama masa testing untuk reset data
            secara cepat.</p>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        <!-- Menggunakan Flux UI agar konsisten dengan project -->
        <flux:input label="Password Rahasia" type="password" wire:model="secret" id="secret"
            placeholder="Masukkan password testing" viewable />

        <div class="pt-4 space-y-3 border-t border-gray-100 dark:border-zinc-800">
            <!-- Tombol Hapus Siswa menggunakan Flux -->
            <flux:button wire:click="deleteAllStudents" variant="danger" class="w-full"
                wire:confirm="Yakin ingin MENGHAPUS SEMUA data Siswa hasil import?">
                Kosongkan Tabel Siswa (Truncate)
            </flux:button>

            <!-- Tambahkan tombol untuk data lain di sini ke depannya -->
            <!--
            <button
                wire:click="deleteOtherData"
                class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                wire:confirm="Yakin ingin menghapus data buku?"
            >
                Hapus Data Buku
            </button>
            -->
        </div>
    </div>
</div>
