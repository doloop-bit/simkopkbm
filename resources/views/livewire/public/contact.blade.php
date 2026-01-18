<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Models\{ContactInquiry, SchoolProfile};
use Illuminate\Support\Facades\Mail;

new #[Layout('components.public.layouts.public')] class extends Component {
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|email|max:255')]
    public $email = '';

    #[Validate('nullable|string|max:20')]
    public $phone = '';

    #[Validate('required|string|max:255')]
    public $subject = '';

    #[Validate('required|string|max:2000')]
    public $message = '';

    public $showSuccess = false;

    public function submit(): void
    {
        $this->validate();

        // Save to database
        ContactInquiry::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'is_read' => false,
        ]);

        // Send email notification (in a real app, you'd queue this)
        try {
            Mail::raw(
                "Pesan baru dari website:\n\n" .
                "Nama: {$this->name}\n" .
                "Email: {$this->email}\n" .
                "Telepon: {$this->phone}\n" .
                "Subjek: {$this->subject}\n\n" .
                "Pesan:\n{$this->message}",
                function ($mail) {
                    $mail->to(config('mail.from.address'))
                         ->subject('Pesan Baru dari Website: ' . $this->subject)
                         ->replyTo($this->email, $this->name);
                }
            );
        } catch (\Exception $e) {
            // Log error but don't fail the form submission
            logger()->error('Failed to send contact email: ' . $e->getMessage());
        }

        // Reset form and show success
        $this->reset(['name', 'email', 'phone', 'subject', 'message']);
        $this->showSuccess = true;

        // Hide success message after 5 seconds
        $this->dispatch('hide-success-message');
    }

    public function hideSuccess(): void
    {
        $this->showSuccess = false;
    }

    public function with(): array
    {
        return [
            'schoolProfile' => SchoolProfile::active(),
            'title' => 'Kontak - ' . config('app.name'),
            'description' => 'Hubungi ' . config('app.name') . ' untuk informasi lebih lanjut tentang program pendidikan dan layanan kami. Kami siap membantu Anda.',
            'keywords' => 'Kontak, Hubungi Kami, Alamat, Telepon, Email, PKBM, Informasi',
            'ogTitle' => 'Kontak - ' . config('app.name'),
            'ogDescription' => 'Hubungi ' . config('app.name') . ' untuk informasi lebih lanjut tentang program pendidikan dan layanan kami. Kami siap membantu Anda.',
        ];
    }
}; ?>

<div>
    <!-- Page Header -->
    <div class="relative bg-gradient-to-br from-green-600 via-green-700 to-emerald-800 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                <defs>
                    <pattern id="contact-grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#contact-grid)" />
            </svg>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4">Hubungi Kami</h1>
                <p class="text-lg sm:text-xl md:text-2xl text-green-100">
                    Kami siap membantu dan menjawab pertanyaan Anda
                </p>
                <div class="w-24 h-1 bg-white/30 mx-auto mt-6 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Contact Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            <!-- Contact Information -->
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Informasi Kontak</h2>
                
                @if($schoolProfile)
                    <div class="space-y-6">
                        <!-- Address -->
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-5 sm:w-6 h-5 sm:h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">Alamat</h3>
                                <p class="text-gray-600 text-sm sm:text-base">{{ $schoolProfile->address }}</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        @if($schoolProfile->phone)
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 sm:w-6 h-5 sm:h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h3 class="text-base sm:text-lg font-medium text-gray-900">Telepon</h3>
                                    <p class="text-gray-600 text-sm sm:text-base">{{ $schoolProfile->phone }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Email -->
                        @if($schoolProfile->email)
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 sm:w-6 h-5 sm:h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h3 class="text-base sm:text-lg font-medium text-gray-900">Email</h3>
                                    <p class="text-gray-600 text-sm sm:text-base">{{ $schoolProfile->email }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Operating Hours -->
                        @if($schoolProfile->operating_hours)
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 sm:w-6 h-5 sm:h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h3 class="text-base sm:text-lg font-medium text-gray-900">Jam Operasional</h3>
                                    <p class="text-gray-600 text-sm sm:text-base">{{ $schoolProfile->operating_hours }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Map -->
                    @if($schoolProfile->latitude && $schoolProfile->longitude)
                        <div class="mt-8">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4">Lokasi</h3>
                            <div class="bg-gray-200 rounded-lg h-48 sm:h-64 flex items-center justify-center">
                                <div class="text-center text-gray-500">
                                    <svg class="w-10 sm:w-12 h-10 sm:h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-sm">Peta akan ditampilkan di sini</p>
                                    <p class="text-xs mt-1">Koordinat: {{ $schoolProfile->latitude }}, {{ $schoolProfile->longitude }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500">Informasi kontak belum tersedia.</p>
                    </div>
                @endif
            </div>

            <!-- Contact Form -->
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Kirim Pesan</h2>

                <!-- Success Message -->
                @if($showSuccess)
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Pesan Terkirim!</h3>
                                <p class="mt-1 text-sm text-green-700">
                                    Terima kasih atas pesan Anda. Kami akan segera menghubungi Anda kembali.
                                </p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button wire:click="hideSuccess" class="text-green-400 hover:text-green-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                <form wire:submit="submit" class="space-y-4 sm:space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            wire:model="name"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-sm sm:text-base"
                            placeholder="Masukkan nama lengkap Anda"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            wire:model="email"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-sm sm:text-base"
                            placeholder="nama@email.com"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Nomor Telepon
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            wire:model="phone"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-sm sm:text-base"
                            placeholder="08xxxxxxxxxx"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Subject -->
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                            Subjek <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="subject" 
                            wire:model="subject"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-sm sm:text-base"
                            placeholder="Subjek pesan Anda"
                        >
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Pesan <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="message" 
                            wire:model="message"
                            rows="5"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-sm sm:text-base"
                            placeholder="Tulis pesan Anda di sini..."
                        ></textarea>
                        @error('message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full flex justify-center py-3 sm:py-4 px-4 sm:px-6 border border-transparent rounded-xl shadow-lg text-sm sm:text-base font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105"
                        >
                            <span wire:loading.remove>Kirim Pesan</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Auto-hide success message -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('hide-success-message', () => {
                setTimeout(() => {
                    Livewire.dispatch('hideSuccess');
                }, 5000);
            });
        });
    </script>
</div>