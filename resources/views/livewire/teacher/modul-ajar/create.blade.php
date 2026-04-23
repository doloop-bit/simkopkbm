<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\ModulAjar;

use App\Models\ModulAjar;
use App\Services\GeminiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
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
    public ?int $selectedMessageIndex = null;

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
            }

            // Update database
            if ($this->moduleId) {
                $modul = ModulAjar::find($this->moduleId);
                $modul->update([
                    'conversation' => $this->messages,
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
            $this->js("window.dispatchEvent(new CustomEvent('update-chat-scroll'));");
        }
    }

    public function selectResponse(int|string $index): void
    {
        Log::info("selectResponse triggered for index: " . $index);
        $index = (int) $index;
        if (!isset($this->messages[$index])) {
            Log::warning("selectResponse failed: index not found in messages.");
            return;
        }

        $this->selectedMessageIndex = $index;
        $this->generatedContent = $this->messages[$index]['parts'][0]['text'];
        Log::info("selectResponse successfully updated generatedContent length: " . strlen($this->generatedContent));
        $this->js("toast('Preview Modul Ajar diperbarui!', { type: 'success' })");
        
        if ($this->moduleId) {
            $modul = ModulAjar::find($this->moduleId);
            $modul->update([
                'generated_content' => $this->generatedContent,
                'status' => 'completed'
            ]);
        }
    }

    public function saveAndDownload()
    {
        if (!$this->moduleId || !$this->generatedContent) {
            $this->js("toast('Silakan pilih salah satu respon AI terlebih dahulu.', { type: 'warning' })");
            return;
        }
        
        $module = ModulAjar::with('user')->find($this->moduleId);
        if ($module->status !== 'completed') {
            $module->update(['status' => 'completed']);
        }
        
        $this->js("toast('Modul berhasil disimpan dan siap diunduh!', { type: 'success' })");
        
        $data = ['module' => $module];
        $pdf = Pdf::loadView('pdf.modul-ajar', $data);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'modul-ajar-'.Str::slug($module->title).'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}; ?>

<div class="h-[calc(100vh-80px)] p-4 max-w-screen-2xl mx-auto flex flex-col">
    
    <div class="mb-4 flex items-center justify-between shrink-0">
        <x-ui.header title="Buat Modul Ajar AI" subtitle="Diskusikan modul Anda dan lihat hasilnya langsung secara berdampingan." class="!mb-0" />
        
        @if(!empty($messages))
            <div class="flex items-center gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-2 rounded-xl shadow-sm">
                <div class="hidden md:block">
                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Topik Aktif</p>
                    <p class="text-sm font-semibold truncate max-w-[200px]">{{ $theme }} - {{ $subject }}</p>
                </div>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 hidden md:block"></div>
                <x-ui.button label="Buat Baru" icon="o-plus" class="btn-xs btn-outline" onclick="window.location.reload()" />
            </div>
        @endif
    </div>

    {{-- Main Grid --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-0">
        
        {{-- Left Pane: Chat & Form --}}
        <div class="lg:col-span-5 flex flex-col min-h-0 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
            
            @if(empty($messages))
                <div class="p-6 overflow-y-auto">
                    <div class="flex items-center gap-3 mb-6 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-4 rounded-xl text-sm">
                        <x-ui.icon name="o-information-circle" class="size-6 shrink-0" />
                        <p>Isi formulir ini untuk memberikan konteks awal agar AI dapat mulai menyusun Modul Ajar Anda.</p>
                    </div>

                    <form wire:submit="startChat" class="space-y-4">
                        <x-ui.input label="Tema / Topik Utama" placeholder="Contoh: Ekosistem Laut, Jual Beli" 
                            wire:model="theme" required />
                        
                        <x-ui.input label="Mata Pelajaran" placeholder="Contoh: IPAS, Bahasa Indonesia" 
                            wire:model="subject" required />
                            
                        <x-ui.input label="Jenjang / Kelas" placeholder="Contoh: Kelas 4 / Fase B" 
                            wire:model="class_level" />

                        <x-ui.textarea label="Deskripsi Khusus (Opsional)" 
                            placeholder="Tambahkan instruksi, misal: Gunakan metode Problem Based Learning." 
                            wire:model="description" rows="3" />

                        <x-ui.button type="submit" label="Memulai Diskusi" icon="o-sparkles" 
                            class="btn-primary w-full mt-4" spinner="startChat" />
                    </form>
                </div>
            @endif

            {{-- Chat Interface --}}
            <div class="{{ empty($messages) ? 'hidden' : 'flex-1 flex flex-col min-h-0 relative bg-slate-50 dark:bg-slate-950/50' }}">
                <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center gap-3 shrink-0">
                    <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                        <x-ui.icon name="o-chat-bubble-bottom-center-text" class="size-4" />
                    </div>
                    <span class="font-bold text-sm">Ruang Diskusi Pribadi</span>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                    @foreach($messages as $key => $msg)
                        <div wire:key="msg-{{ $key }}" class="flex flex-col {{ $msg['role'] === 'user' ? 'items-end' : 'items-start' }} gap-1 mb-4">
                            <span class="text-[10px] text-slate-500 font-medium px-1">{{ $msg['role'] === 'user' ? 'Anda' : 'SIUBA AI' }}</span>
                            <div class="max-w-[90%] rounded-2xl p-4 {{ $msg['role'] === 'user' ? 'bg-primary text-white rounded-tr-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-tl-sm shadow-sm' }}">
                                <div class="prose prose-sm {{ $msg['role'] === 'user' ? 'prose-p:text-white prose-headings:text-white' : 'dark:prose-invert' }} max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($msg['parts'][0]['text']) !!}
                                </div>
                            </div>
                            
                            @if($msg['role'] === 'model' && !$isGenerating)
                                @php $isCurrent = ($selectedMessageIndex === $key); @endphp
                                <x-ui.button wire:click="selectResponse({{ $key }})" 
                                    :label="$isCurrent ? 'Modul Ditampilkan' : 'Terapkan & Lihat'" 
                                    :icon="$isCurrent ? 'o-check-circle' : 'o-eye'" 
                                    spinner="selectResponse({{ $key }})"
                                    class="btn-xs mt-1 {{ $isCurrent ? 'btn-success text-white ring-2 ring-success/20 ring-offset-1' : 'btn-ghost bg-white/50 border border-slate-200 hover:bg-slate-100' }} rounded-full transition-all" />
                            @endif
                        </div>
                    @endforeach

                    @if($isGenerating)
                        <div class="flex flex-col items-start gap-1">
                            <span class="text-[10px] text-slate-500 font-medium px-1">SIUBA AI</span>
                            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl rounded-tl-sm p-4 shadow-sm w-32 flex justify-center">
                                <span class="loading loading-dots loading-sm opacity-50"></span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Input Area --}}
                <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                    <form wire:submit="sendMessage" class="flex gap-2">
                        <input type="text" wire:model="newMessage" 
                            class="flex-1 bg-slate-100 dark:bg-slate-800 border-transparent focus:bg-white focus:text-black focus:border-primary/50 focus:ring-2 focus:ring-primary/20 rounded-xl px-4 text-sm dark:text-white"
                            placeholder="Revisi modul ini... (misal: kurangi durasi waktunya)" :disabled="$isGenerating">
                        <x-ui.button type="submit" icon="o-paper-airplane" class="btn-primary rounded-xl" 
                            spinner="sendMessage" :disabled="$isGenerating" />
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Pane: Live Preview --}}
        <div class="lg:col-span-7 flex flex-col min-h-0 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden relative group">
            
            {{-- Preview Header --}}
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2 px-2">
                    <div class="size-8 rounded bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                        <x-ui.icon name="o-document-text" class="size-5" />
                    </div>
                    <div>
                        <span class="font-bold text-sm block">Live Preview</span>
                        <span class="text-[10px] text-slate-500">Tampilan dokumen PDF</span>
                    </div>
                </div>
                
                <div>
                    @if($generatedContent)
                        <x-ui.button wire:click="saveAndDownload" label="Unduh PDF" icon="o-cloud-arrow-down" 
                            class="btn-success btn-sm text-white shadow-md animate-bounce hover:animate-none" spinner="saveAndDownload" />
                    @else
                        <x-ui.button label="Pilih Respon..." icon="o-document" 
                            class="btn-disabled btn-sm" disabled />
                    @endif
                </div>
            </div>

            {{-- Preview Area --}}
            <div class="flex-1 overflow-y-auto bg-slate-100 dark:bg-slate-950 p-4 sm:p-8">
                @if($generatedContent)
                    <div class="bg-white dark:bg-slate-900 min-h-full p-8 sm:p-12 shadow-sm rounded-lg border border-slate-200 dark:border-slate-800">
                        <div class="prose prose-slate dark:prose-invert max-w-none prose-headings:text-slate-800 dark:prose-headings:text-slate-200 prose-a:text-primary">
                            {!! \Illuminate\Support\Str::markdown($generatedContent) !!}
                        </div>
                    </div>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                        <div class="size-32 rounded-full border-4 border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-center">
                            <x-ui.icon name="o-document-magnifying-glass" class="size-16 opacity-30" />
                        </div>
                        <div class="text-center max-w-sm">
                            <h4 class="font-bold text-slate-600 dark:text-slate-400 text-lg mb-2">Area Preview Modul</h4>
                            <p class="text-sm opacity-80">Setelah AI memberikan jawaban, klik tombol <b>"Terapkan & Lihat"</b> pada pesan AI untuk menampilkan hasil akhirnya di sini.</p>
                        </div>
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const scrollToBottom = () => {
            setTimeout(() => {
                const el = document.getElementById('chat-messages');
                if(el) {
                    el.scrollTop = el.scrollHeight;
                }
            }, 50);
        };
        
        Livewire.on('scrollToBottom', scrollToBottom);
        window.addEventListener('update-chat-scroll', scrollToBottom);
    });
</script>
