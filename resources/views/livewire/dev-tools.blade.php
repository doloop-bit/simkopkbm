<?php

use Livewire\Component;
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
            session()->flash('error', 'Otoritas Gagal: Password Rahasia Salah!');
            return;
        }

        DB::transaction(function () {
            // Nonaktifkan constraint
            Schema::disableForeignKeyConstraints();

            // 1. Hapus User dengan role siswa
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

            // Hapus data periodik
            \App\Models\StudentPeriodicRecord::truncate();

            Schema::enableForeignKeyConstraints();
        });

        session()->flash('success', 'Purging Berhasil: Seluruh data siswa (User, Profil, & Data Periodik) telah dihapus dari sistem.');

        $this->reset('secret');
    }

    public function deleteOtherData()
    {
        // Placeholder for future tools
    }
};
?>

<div class="h-screen flex items-center justify-center p-6 bg-slate-50 dark:bg-slate-950">
    <div class="max-w-xl w-full space-y-6">
        @if (session('success'))
            <x-ui.alert :title="__('Operasi Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100 shadow-xl shadow-emerald-500/10" dismissible>
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert :title="__('Akses Ditolak')" icon="o-exclamation-circle" class="bg-rose-50 text-rose-800 border-rose-100 shadow-xl shadow-rose-500/10" dismissible>
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <x-ui.card shadow padding="false" class="border-none ring-1 ring-slate-200 dark:ring-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-2xl">
            <div class="p-8 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 dark:text-white uppercase tracking-tighter italic leading-none">{{ __('System Purging Tools') }}</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">{{ __('Hanya untuk pengembangan & pengujian internal') }}</p>
                </div>
                <x-ui.badge :label="__('CRITICAL ACCESS')" class="bg-rose-500 text-white border-none font-black italic text-[8px] px-3 py-1 shadow-lg shadow-rose-500/20" />
            </div>

            <div class="p-8 space-y-8">
                <div class="space-y-4">
                    <x-ui.input 
                        :label="__('Kode Otoritas Rahasia')" 
                        type="password" 
                        wire:model="secret" 
                        :placeholder="__('Masukkan passphrase pengembang...')" 
                        icon="o-key"
                        class="font-mono tracking-[0.5em] text-center text-lg"
                    />
                    <p class="text-[9px] text-slate-400 font-bold italic text-center uppercase tracking-widest">{{ __('Masukkan password yang tepat untuk membuka blokir fitur penghapusan.') }}</p>
                </div>

                <div class="pt-8 border-t border-slate-50 dark:border-slate-800 space-y-4">
                    <div class="flex items-center gap-3 text-rose-500 bg-rose-50 dark:bg-rose-950/30 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/50">
                        <x-ui.icon name="o-exclamation-triangle" class="size-6 shrink-0" />
                        <div class="text-[11px] font-bold italic leading-relaxed uppercase tracking-tight">
                            {{ __('Tindakan di bawah ini bersifat IRREVERSIBLE. Pastikan Anda telah melakukan backup database jika diperlukan.') }}
                        </div>
                    </div>

                    <x-ui.button 
                        :label="__('Kosongkan Seluruh Basis Data Siswa')" 
                        icon="o-trash" 
                        class="w-full btn-ghost text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950 border-2 border-dashed border-rose-100 dark:border-rose-900 font-black italic uppercase tracking-tighter py-6 h-auto" 
                        wire:click="deleteAllStudents"
                        wire:confirm="__('WASPADA: Anda akan menghapus SELURUH data siswa beserta user dan profilnya. Lanjutkan?')"
                        spinner="deleteAllStudents"
                    />
                </div>
            </div>

            <div class="px-8 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800">
                <p class="text-[8px] font-black text-slate-300 dark:text-slate-700 text-center uppercase tracking-[0.25em]">
                    {{ __('Antigravity UI System Integrity • Build 2025.03') }}
                </p>
            </div>
        </x-ui.card>
    </div>
</div>
