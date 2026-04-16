<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\ModulAjar;

use App\Models\ModulAjar;
use App\Services\GeminiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.app')] class extends Component {
    public string $theme = '';
    public string $subject = '';
    public string $class_level = '';
    public string $description = '';
    
    public array $messages = [];
    public string $newMessage = '';
    public bool $isGenerating = false;
    public ?int $moduleId = null;
    public ?string $generatedContent = null;

    protected string $systemPrompt = "Anda adalah asisten AI ahli kurikulum pendidikan di Indonesia, khusus untuk Kurikulum Merdeka. 
Tugas Anda adalah membantu guru menyusun 'Modul Ajar' yang lengkap, sistematis, dan sesuai standar.

Aturan Interaksi:
1. Jika informasi dari guru masih sangat minim (misal hanya tema atau subjek saja tanpa kelas atau deskripsi yang jelas), jangan langsung membuat modul. SEBALIKNYA, ajukan pertanyaan klarifikasi yang ramah untuk melengkapi data (seperti kelas berapa, alokasi waktu, atau sarana apa yang tersedia).
2. Jika data sudah cukup, susunlah Modul Ajar lengkap dengan format Markdown yang mencakup:
   - Informasi Umum (Satuan Pendidikan, Kelas, Mapel, Fase, Alokasi Waktu)
   - Kompetensi Awal
   - Profil Pelajar Pancasila
   - Sarana & Prasarana
   - Target Peserta Didik
   - Tujuan Pembelajaran (TP)
   - Pemahaman Bermakna
   - Pertanyaan Pemantik
   - Kegiatan Pembelajaran (Pendahuluan, Inti, Penutup)
   - Asesmen (Formatif & Sumatif)
   - Refleksi
3. Gunakan bahasa Indonesia yang profesional namun tetap inspiratif bagi guru.
4. JANGAN pernah menyebutkan nama sekolah lain selain 'SIUBA' jika konteks menuntut nama sekolah.
5. Pastikan modul ajar berpusat pada siswa (student-centered).";

    public function startChat(): void
    {
        if ($this->isGenerating) return;

        $this->validate([
            'theme' => 'required|string|min:3',
            'subject' => 'required|string',
        ]);

        $initialPrompt = "Halo AI, saya ingin membuat Modul Ajar untuk tema: '{$this->theme}' pada mata pelajaran '{$this->subject}'. 
Kelas: " . ($this->class_level ?: 'Belum ditentukan') . ".
Deskripsi singkat: " . ($this->description ?: 'Beri saya ide yang kreatif.') . ".";

        $this->messages[] = [
            'role' => 'user',
            'parts' => [['text' => $initialPrompt]]
        ];

        $this->isGenerating = true;
        
        $this->moduleId = ModulAjar::create([
            'user_id' => auth()->id(),
            'title' => $this->theme,
            'subject' => $this->subject,
            'class_level' => $this->class_level,
            'description' => $this->description,
            'status' => 'generating',
            'conversation' => $this->messages
        ])->id;

        $this->sendMessageToAI();
    }

    public function sendMessage(): void
    {
        if ($this->isGenerating || empty(trim($this->newMessage))) return;

        $this->messages[] = [
            'role' => 'user',
            'parts' => [['text' => $this->newMessage]]
        ];

        $messageText = $this->newMessage;
        $this->newMessage = '';
        $this->isGenerating = true;

        $this->sendMessageToAI();
    }

    protected function sendMessageToAI(): void
    {
        try {
            $gemini = new GeminiService();
            $response = $gemini->chat($this->messages, $this->systemPrompt);

            if (str_starts_with($response, 'Error:')) {
                $this->messages[] = [
                    'role' => 'model',
                    'parts' => [['text' => "Maaf, terjadi masalah saat menghubungi AI: " . str_replace('Error: ', '', $response)]]
                ];
            } else {
                $this->messages[] = [
                    'role' => 'model',
                    'parts' => [['text' => $response]]
                ];

                // Check if the response looks like a complete module
                if (str_contains($response, 'Informasi Umum') && str_contains($response, 'Kegiatan Pembelajaran')) {
                    $this->generatedContent = $response;
                }
            }

            // Update database
            if ($this->moduleId) {
                $modul = ModulAjar::find($this->moduleId);
                $modul->update([
                    'conversation' => $this->messages,
                    'generated_content' => $this->generatedContent,
                    'status' => $this->generatedContent ? 'completed' : 'generating'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Chat Error: ' . $e->getMessage());
            $this->messages[] = [
                'role' => 'model',
                'parts' => [['text' => "Maaf, sistem mengalami gangguan teknis. Silakan coba lagi nanti."]]
            ];
        } finally {
            $this->isGenerating = false;
        }
    }

    public function saveModule(): void
    {
        if (!$this->moduleId || !$this->generatedContent) return;
        
        $this->js("toast('Modul ajar berhasil disimpan!', { type: 'success' })");
        $this->redirectRoute('teacher.modul-ajar.show', ['id' => $this->moduleId], navigate: true);
    }
}; ?>

<div class="p-4 md:p-6 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- Input Form (Left) --}}
        <div class="lg:col-span-4 space-y-6 sticky top-6">
            <x-ui.header title="Buat Modul Ajar" subtitle="Lengkapi detail untuk membantu AI menyusun modul." />
            
            <x-ui.card shadow class="bg-white/80 dark:bg-slate-900/80 backdrop-blur">
                <form wire:submit="startChat" class="space-y-4">
                    <x-ui.input label="Tema / Topik Utama" placeholder="Contoh: Ekosistem Laut, Jual Beli" 
                        wire:model="theme" required :disabled="!empty($messages)" />
                    
                    <x-ui.input label="Mata Pelajaran" placeholder="Contoh: IPAS, Bahasa Indonesia" 
                        wire:model="subject" required :disabled="!empty($messages)" />
                        
                    <x-ui.input label="Jenjang / Kelas" placeholder="Contoh: Kelas 4 / Fase B" 
                        wire:model="class_level" :disabled="!empty($messages)" />

                    <x-ui.textarea label="Deskripsi (Opsional)" 
                        placeholder="Tambahkan instruksi khusus, misal: Fokus pada kegiatan kelompok." 
                        wire:model="description" rows="3" :disabled="!empty($messages)" />

                    @if(empty($messages))
                        <x-ui.button type="submit" label="Mulai Diskusi dengan AI" icon="o-sparkles" 
                            class="btn-primary w-full" spinner="startChat" />
                    @else
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800/30">
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                Sesi chat sedang aktif. Anda bisa memberikan instruksi tambahan pada kolom chat di samping.
                            </p>
                            <x-ui.button label="Reset / Buat Baru" class="btn-xs btn-ghost mt-2" 
                                onclick="window.location.reload()" />
                        </div>
                    @endif
                </form>
            </x-ui.card>

            @if($generatedContent)
                <x-ui.button wire:click="saveModule" label="Simpan Modul & Lihat Hasil" icon="o-check-circle" 
                    class="btn-success w-full py-4 text-lg shadow-lg hover:scale-[1.02] transition-transform" />
            @endif
        </div>

        {{-- Chat Interface (Right) --}}
        <div class="lg:col-span-8 flex flex-col h-[70vh] lg:h-[80vh] bg-slate-50 dark:bg-slate-950 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
            
            {{-- Chat Header --}}
            <div class="p-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-full bg-emerald-500 flex items-center justify-center text-white">
                        <x-ui.icon name="o-cpu-chip" class="size-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-100">SIUBA AI Assistant</h3>
                        <p class="text-xs text-emerald-500 flex items-center gap-1">
                            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span> Online & Siap Membantu
                        </p>
                    </div>
                </div>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                @if(empty($messages))
                    <div class="h-full flex flex-col items-center justify-center text-center opacity-40 grayscale space-y-4">
                        <x-ui.icon name="o-chat-bubble-left-right" class="size-20" />
                        <p class="max-w-xs text-sm">Input data di samping untuk memulai diskusi penyusunan Modul Ajar.</p>
                    </div>
                @else
                    @foreach($messages as $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl p-4 {{ $msg['role'] === 'user' ? 'bg-primary text-white rounded-tr-none' : 'bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border border-slate-100 dark:border-slate-800 rounded-tl-none shadow-sm' }}">
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($msg['parts'][0]['text']) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if($isGenerating)
                    <div class="flex justify-start">
                        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl rounded-tl-none p-4 shadow-sm flex items-center gap-2">
                             <span class="loading loading-dots loading-sm opacity-50"></span>
                             <span class="text-xs text-slate-400 italic">AI sedang berpikir...</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Input Area --}}
            <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
                <form wire:submit="sendMessage" class="flex gap-2">
                    <input type="text" wire:model="newMessage" 
                        class="flex-1 bg-slate-50 dark:bg-slate-950 border-none focus:ring-2 focus:ring-primary/20 rounded-xl px-4 text-sm"
                        placeholder="Ketik instruksi atau jawaban..." :disabled="empty($messages) || $isGenerating">
                    <x-ui.button type="submit" icon="o-paper-airplane" class="btn-primary rounded-xl" 
                        spinner="sendMessage" :disabled="empty($messages) || $isGenerating" />
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('scrollToBottom', () => {
            setTimeout(() => {
                const el = document.getElementById('chat-messages');
                el.scrollTop = el.scrollHeight;
            }, 50);
        });
    });
</script>
