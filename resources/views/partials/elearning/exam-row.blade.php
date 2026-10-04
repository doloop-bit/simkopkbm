<div class="flex items-center justify-between gap-3 py-2 px-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/30 hover:border-amber-300 transition-colors" wire:key="exam-{{ $exam->id }}">
    <div class="flex items-center gap-2.5 min-w-0">
        @php
            $typeMeta = match ($exam->exam_type) {
                'midterm' => ['badge' => 'UTS', 'variant' => 'warning'],
                'final' => ['badge' => 'UAS', 'variant' => 'error'],
                default => ['badge' => 'Kuis Bab', 'variant' => 'info'],
            };
        @endphp
        <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 shrink-0">
            <x-ui.icon name="o-question-mark-circle" class="w-4 h-4" />
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $exam->title }}</span>
                <x-ui.badge :label="$typeMeta['badge']" :variant="$typeMeta['variant']" flat size="xs" />
                @unless($exam->is_published)
                    <x-ui.badge :label="__('Draf')" variant="amber" flat size="xs" />
                @endunless
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                <span class="font-medium text-amber-700 dark:text-amber-400">{{ $exam->questions_count }} {{ __('soal') }}</span>
                <span>•</span>
                <span>{{ $exam->duration_minutes }} {{ __('menit') }}</span>
                <span>•</span>
                <span>KKM: {{ $exam->passing_grade }}</span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-1 shrink-0">
        <x-ui.button :label="__('Kelola Soal')" icon="o-list-bullet" ghost size="xs" class="text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 font-medium" :link="route('admin.elearning.exam-questions', $exam->id)" />
        <x-ui.button :icon="$exam->is_published ? 'o-eye' : 'o-eye-slash'" ghost size="xs" wire:click="toggleExam({{ $exam->id }})" :title="$exam->is_published ? __('Sembunyikan (Jadikan Draf)') : __('Terbitkan')" />
        <x-ui.button icon="o-pencil-square" ghost size="xs" wire:click="editExam({{ $exam->id }})" :title="__('Edit Kuis / Ujian')" />
        <x-ui.button icon="o-trash" ghost size="xs" class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20" wire:confirm="{{ __('Hapus kuis/ujian ini beserta soalnya?') }}" wire:click="deleteExam({{ $exam->id }})" :title="__('Hapus')" />
    </div>
</div>
