<div class="flex items-center justify-between gap-2 py-1.5 px-2 rounded-lg bg-amber-50/60 dark:bg-amber-950/20" wire:key="exam-{{ $exam->id }}">
    <div class="flex items-center gap-2 min-w-0">
        <x-ui.icon name="o-question-mark-circle" class="w-4 h-4 text-amber-600 shrink-0" />
        <span class="text-sm font-medium truncate">{{ $exam->title }}</span>
        <x-ui.badge :label="match ($exam->exam_type) { 'midterm' => 'UTS', 'final' => 'UAS', default => 'Kuis' }" flat size="xs" />
        <span class="text-xs text-slate-500">{{ $exam->questions_count }} {{ __('soal') }}</span>
        @unless($exam->is_published)
            <x-ui.badge :label="__('Draf')" variant="amber" flat size="xs" />
        @endunless
    </div>
    <div class="flex items-center gap-0.5 shrink-0">
        <x-ui.button :label="__('Soal')" icon="o-list-bullet" ghost size="xs" :link="route('admin.elearning.exam-questions', $exam->id)" />
        <x-ui.button :icon="$exam->is_published ? 'o-eye' : 'o-eye-slash'" ghost size="xs" wire:click="toggleExam({{ $exam->id }})" :title="__('Terbit / Draf')" />
        <x-ui.button icon="o-pencil-square" ghost size="xs" wire:click="editExam({{ $exam->id }})" />
        <x-ui.button icon="o-trash" ghost size="xs" class="text-red-600" wire:confirm="{{ __('Hapus kuis/ujian ini beserta soalnya?') }}" wire:click="deleteExam({{ $exam->id }})" />
    </div>
</div>
