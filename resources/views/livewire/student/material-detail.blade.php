<?php

declare(strict_types=1);

use App\Models\OnlineMaterial;
use App\Services\ElearningCourseOutlineService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.student')] class extends Component {
    public OnlineMaterial $material;

    public function mount(int $materialId, ElearningCourseOutlineService $outlineService): void
    {
        $student = auth()->user();
        $this->material = OnlineMaterial::where('is_published', true)
            ->with(['subject', 'chapter', 'classroom', 'academicYear', 'creator'])
            ->findOrFail($materialId);

        // Check if material is accessible (not locked)
        if (!$outlineService->isMaterialAccessible($student, $this->material)) {
            session()->flash('error', 'Materi ini masih terkunci. Selesaikan materi/kuis sebelumnya terlebih dahulu.');
            $this->redirect(route('student.subject-outline', $this->material->subject_id));
            return;
        }

        // Mark progress automatically upon viewing
        $outlineService->markMaterialAsCompleted($student, $this->material->id);
    }
}; ?>

<div class="p-6 space-y-6 max-w-4xl mx-auto">
    {{-- Header --}}
    <div>
        <a href="{{ route('student.subject-outline', $material->subject_id) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-emerald-600 mb-3 font-medium">
            <x-ui.icon name="o-arrow-left" class="w-4 h-4" />
            <span>Kembali ke Outline {{ $material->subject?->name }}</span>
        </a>

        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    @if($material->chapter)
                        <x-ui.badge :label="'Bab ' . $material->chapter->order . ': ' . $material->chapter->title" variant="info" size="xs" />
                    @else
                        <x-ui.badge label="Buku Pegangan Utama" variant="warning" size="xs" />
                    @endif
                    <x-ui.badge label="Selesai Dibaca" variant="success" flat size="xs" />
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $material->title }}</h1>
            </div>
        </div>
    </div>

    {{-- VIDEO COMPONENT --}}
    @if($material->type === 'video' && $material->video_url)
        <x-ui.card shadow class="overflow-hidden p-0">
            @php
                $videoUrl = $material->video_url;
                $embedUrl = null;
                if (str_contains($videoUrl, 'youtube.com/watch?v=')) {
                    $videoId = explode('v=', $videoUrl)[1] ?? '';
                    $videoId = explode('&', $videoId)[0];
                    $embedUrl = "https://www.youtube.com/embed/{$videoId}";
                } elseif (str_contains($videoUrl, 'youtu.be/')) {
                    $videoId = explode('youtu.be/', $videoUrl)[1] ?? '';
                    $videoId = explode('?', $videoId)[0];
                    $embedUrl = "https://www.youtube.com/embed/{$videoId}";
                } elseif (str_contains($videoUrl, 'drive.google.com') && str_contains($videoUrl, '/view')) {
                    $embedUrl = str_replace('/view', '/preview', $videoUrl);
                }
            @endphp

            @if($embedUrl)
                <div class="relative w-full aspect-video bg-black">
                    <iframe src="{{ $embedUrl }}" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                </div>
            @else
                <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-ui.icon name="o-video-camera" class="w-8 h-8 text-rose-500" />
                        <div>
                            <h4 class="font-bold text-sm">Tautan Video Eksternal</h4>
                            <p class="text-xs text-slate-400">Tonton video melalui tautan langsung</p>
                        </div>
                    </div>
                    <a href="{{ $videoUrl }}" target="_blank" class="btn btn-sm btn-primary">
                        Buka Video <x-ui.icon name="o-arrow-top-right-on-square" class="w-4 h-4 ml-1" />
                    </a>
                </div>
            @endif

            @if($material->transcript)
                <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <x-ui.icon name="o-document-text" class="w-4 h-4 text-emerald-600" />
                        Transkrip & Ringkasan Video
                    </h3>
                    <div class="prose dark:prose-invert max-w-none text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">
                        {!! nl2br(e($material->transcript)) !!}
                    </div>
                </div>
            @endif
        </x-ui.card>
    @endif

    {{-- TEXT CONTENT --}}
    @if($material->content)
        <x-ui.card shadow>
            <div class="prose dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">
                {!! nl2br(e($material->content)) !!}
            </div>
        </x-ui.card>
    @endif

    {{-- ATTACHMENTS & PRESENTATION PDF VIEWER --}}
    @if($material->attachments && count($material->attachments) > 0)
        <x-ui.card shadow space-y-4>
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <x-ui.icon name="o-paper-clip" class="w-5 h-5 text-emerald-600" />
                File Lampiran & Slide Presentasi
            </h3>

            <div class="space-y-3">
                @foreach($material->attachments as $attachment)
                    @php
                        $fileUrl = Storage::url($attachment['path']);
                        $isPdf = str_contains(strtolower($attachment['name'] ?? ''), '.pdf') || str_contains($attachment['mime'] ?? '', 'pdf');
                    @endphp

                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-800/40">
                        <div class="flex items-center justify-between p-3.5 px-4 bg-white dark:bg-slate-800">
                            <div class="flex items-center gap-3">
                                <x-ui.icon name="o-document-arrow-down" class="w-6 h-6 text-emerald-600 shrink-0" />
                                <div>
                                    <span class="font-semibold text-sm text-slate-900 dark:text-white block">{{ $attachment['name'] }}</span>
                                    <span class="text-xs text-slate-400">{{ number_format(($attachment['size'] ?? 0) / 1024, 1) }} KB</span>
                                </div>
                            </div>
                            <a href="{{ $fileUrl }}" download target="_blank" class="btn btn-sm btn-ghost text-emerald-600">
                                Unduh <x-ui.icon name="o-arrow-down-tray" class="w-4 h-4 ml-1" />
                            </a>
                        </div>

                        {{-- PDF Preview iframe if PDF --}}
                        @if($isPdf)
                            <div class="border-t border-slate-200 dark:border-slate-700 h-[500px] w-full bg-slate-200">
                                <iframe src="{{ $fileUrl }}" class="w-full h-full" frameborder="0"></iframe>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Bottom Navigation --}}
    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
        <a href="{{ route('student.subject-outline', $material->subject_id) }}" wire:navigate class="btn btn-outline btn-sm">
            <x-ui.icon name="o-arrow-left" class="w-4 h-4 mr-1" /> Kembali ke Modul Bab
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                <x-ui.icon name="o-check-circle" class="w-4 h-4" /> Progres Tersimpan
            </span>
        </div>
    </div>
</div>
