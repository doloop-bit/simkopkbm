<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public $photo = null;
    public ?string $currentPhoto = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $this->currentPhoto = $user->photo ?? ($user->studentProfile?->photo ?? null);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        if ($this->photo) {
            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            if ($user->studentProfile?->photo && Storage::disk('public')->exists($user->studentProfile->photo)) {
                Storage::disk('public')->delete($user->studentProfile->photo);
            }

            $storedPath = $this->photo->store('avatars', 'public');
            $user->photo = $storedPath;

            // Also keep studentProfile photo in sync if student
            if ($user->studentProfile) {
                $user->studentProfile->update(['photo' => $storedPath]);
            }

            $this->currentPhoto = $storedPath;
            $this->photo = null;
        }

        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->email = $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Remove the current profile photo.
     */
    public function deletePhoto(): void
    {
        $user = Auth::user();

        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }
        $user->photo = null;
        $user->save();

        if ($user->studentProfile) {
            if ($user->studentProfile->photo && Storage::disk('public')->exists($user->studentProfile->photo)) {
                Storage::disk('public')->delete($user->studentProfile->photo);
            }
            $user->studentProfile->update(['photo' => null]);
        }

        $this->currentPhoto = null;
        $this->photo = null;

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-admin.settings.layout :heading="__('Profile')" :subheading="__('Perbarui foto profil, nama, email, dan nomor telepon Anda')">
        @if(auth()->user()?->isSiswa())
            @php
                $studentUser = auth()->user();
                $studentProfile = $studentUser->studentProfile ?? $studentUser->latestProfile?->profileable;
                $classroom = $studentProfile?->classroom;
            @endphp
            @if($studentProfile)
                <div class="mb-6 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs sm:text-sm">
                    <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <x-ui.icon name="o-academic-cap" class="w-4 h-4 text-emerald-600" />
                        {{ __('Informasi Akademik Siswa') }}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 dark:text-slate-400">
                        <div>NIS: <strong class="text-slate-900 dark:text-white">{{ $studentProfile->nis ?? '-' }}</strong></div>
                        <div>NISN: <strong class="text-slate-900 dark:text-white">{{ $studentProfile->nisn ?? '-' }}</strong></div>
                        <div>Kelas: <strong class="text-slate-900 dark:text-white">{{ $classroom?->name ?? '-' }}</strong></div>
                        <div>Status: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Siswa Aktif Daring') }}</span></div>
                    </div>
                </div>
            @endif
        @endif

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            {{-- Profile Photo Upload Section --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 flex flex-col sm:flex-row items-center sm:items-start gap-5">
                <div class="relative shrink-0">
                    @if ($photo)
                        {{-- New Upload Temporary Preview --}}
                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover ring-2 ring-emerald-500 shadow-md">
                    @elseif ($currentPhoto && Storage::disk('public')->exists($currentPhoto))
                        {{-- Current Stored Photo --}}
                        <img src="{{ Storage::url($currentPhoto) }}" alt="{{ $name }}" class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover ring-2 ring-slate-200 dark:ring-slate-700 shadow-sm">
                    @else
                        {{-- Initial / Fallback Icon --}}
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-950/60 dark:to-teal-950/40 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-bold text-2xl shadow-inner">
                            @if(!empty($name))
                                {{ strtoupper(substr($name, 0, 1)) }}
                            @else
                                <x-ui.icon name="o-user" class="w-10 h-10 opacity-70" />
                            @endif
                        </div>
                    @endif

                    <div wire:loading wire:target="photo" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs rounded-2xl flex items-center justify-center text-white">
                        <x-ui.icon name="o-arrow-path" class="w-6 h-6 animate-spin" />
                    </div>
                </div>

                <div class="flex-1 text-center sm:text-left space-y-2">
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Foto Profil') }}</div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('Format gambar: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                        {{-- File Input Button --}}
                        <input type="file" wire:model="photo" id="profile_photo_input" accept="image/png,image/jpeg,image/webp,image/jpg" class="hidden">
                        <label for="profile_photo_input" class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 shadow-xs transition-colors">
                            <x-ui.icon name="o-arrow-up-tray" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span>{{ __('Pilih Foto') }}</span>
                        </label>

                        @if ($photo)
                            <button type="button" wire:click="$set('photo', null)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
                                {{ __('Batal') }}
                            </button>
                        @elseif ($currentPhoto)
                            <button type="button" wire:click="deletePhoto" wire:confirm="{{ __('Apakah Anda yakin ingin menghapus foto profil?') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-colors">
                                <x-ui.icon name="o-trash" class="w-4 h-4" />
                                <span>{{ __('Hapus Foto') }}</span>
                            </button>
                        @endif
                    </div>

                    @error('photo')
                        <div class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <x-ui.input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
            
            <x-ui.input wire:model="phone" :label="__('Phone Number (WhatsApp)')" type="tel" placeholder="08xxxxxxxx" />

            <div>
                <x-ui.input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div class="mt-4">
                        <p class="text-sm opacity-70 italic">
                            {{ __('Your email address is unverified.') }}

                            <button type="button" class="text-primary hover:underline text-sm font-medium" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-emerald-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <x-ui.button :label="__('Save')" type="submit" class="btn-primary" spinner="updateProfileInformation" data-test="update-profile-button" />

                <x-admin.action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-admin.action-message>
            </div>
        </form>

        @if(!auth()->user()?->isSiswa())
            <livewire:admin.settings.delete-user-form />
        @endif
    </x-admin.settings.layout>
</section>
