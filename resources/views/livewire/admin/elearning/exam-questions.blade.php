<?php

declare(strict_types=1);

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public OnlineExam $exam;

    // Form fields
    public bool $questionModal = false;
    public ?OnlineExamQuestion $editing = null;
    public string $question_type = 'multiple_choice';
    public string $question_text = '';
    public array $options = ['', '', '', ''];
    public string $correct_answer = '';
    public int $points = 1;
    public int $order = 0;

    public function mount(int $examId): void
    {
        $this->exam = OnlineExam::with(['subject', 'classroom'])->findOrFail($examId);
    }

    public function rules(): array
    {
        $rules = [
            'question_type' => ['required', 'in:multiple_choice,essay,file_upload'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'order' => ['integer', 'min:0'],
        ];

        if ($this->question_type === 'multiple_choice') {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*'] = ['required', 'string'];
            $rules['correct_answer'] = ['required', 'string'];
        }

        return $rules;
    }

    public function createNew(): void
    {
        $this->reset(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'editing']);
        $this->options = ['', '', '', ''];
        $this->points = 1;
        $this->order = $this->exam->questions()->count();
        $this->questionModal = true;
    }

    public function edit(OnlineExamQuestion $question): void
    {
        $this->editing = $question;
        $this->question_type = $question->question_type;
        $this->question_text = $question->question_text;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->correct_answer = $question->correct_answer ?? '';
        $this->points = $question->points;
        $this->order = $question->order;
        $this->questionModal = true;
    }

    public function addOption(): void
    {
        $this->options[] = '';
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            array_splice($this->options, $index, 1);
            $this->options = array_values($this->options);
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'online_exam_id' => $this->exam->id,
            'question_type' => $this->question_type,
            'question_text' => $this->question_text,
            'options' => $this->question_type === 'multiple_choice' ? array_values(array_filter($this->options, fn ($o) => trim($o) !== '')) : null,
            'correct_answer' => $this->question_type === 'multiple_choice' ? $this->correct_answer : null,
            'points' => $this->points,
            'order' => $this->order,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            OnlineExamQuestion::create($data);
        }

        $this->reset(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'editing']);
        $this->questionModal = false;
    }

    public function delete(OnlineExamQuestion $question): void
    {
        $question->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            OnlineExamQuestion::where('id', $id)->update(['order' => $index]);
        }
    }

    public function with(): array
    {
        return [
            'questions' => $this->exam->questions()->orderBy('order')->get(),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header
        :title="__('Kelola Soal: ') . $exam->title"
        :subtitle="$exam->subject?->name . ' — ' . $exam->classroom?->name"
        separator
    >
        <x-slot:actions>
            <x-ui.button :label="__('Kembali')" icon="o-arrow-left" :link="route('admin.elearning.exams')" ghost />
            <x-ui.button :label="__('Tambah Soal')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $questions->count() }}</div>
                <div class="text-sm text-slate-500">{{ __('Total Soal') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $questions->sum('points') }}</div>
                <div class="text-sm text-slate-500">{{ __('Total Poin') }}</div>
            </div>
        </x-ui.card>
        <x-ui.card shadow>
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $exam->duration_minutes ?? '∞' }}</div>
                <div class="text-sm text-slate-500">{{ __('Durasi (menit)') }}</div>
            </div>
        </x-ui.card>
    </div>

    {{-- Questions List --}}
    <div class="space-y-4">
        @forelse($questions as $index => $question)
            <x-ui.card shadow>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-bold">
                                {{ $index + 1 }}
                            </span>
                            @php
                                $typeLabels = [
                                    'multiple_choice' => ['Pilihan Ganda', 'success'],
                                    'essay' => ['Essay', 'info'],
                                    'file_upload' => ['Upload File', 'amber'],
                                ];
                                $typeInfo = $typeLabels[$question->question_type] ?? ['Unknown', 'secondary'];
                            @endphp
                            <x-ui.badge :label="$typeInfo[0]" :variant="$typeInfo[1]" flat size="xs" />
                            <span class="text-xs text-slate-500">{{ $question->points }} {{ __('poin') }}</span>
                        </div>

                        <div class="text-slate-900 dark:text-white mb-3 whitespace-pre-line">{{ $question->question_text }}</div>

                        @if($question->question_type === 'multiple_choice' && $question->options)
                            <div class="space-y-1 ml-11">
                                @foreach($question->options as $optIndex => $option)
                                    <div class="flex items-center gap-2 text-sm {{ $question->correct_answer === chr(65 + $optIndex) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-600 dark:text-slate-400' }}">
                                        <span class="w-6 h-6 flex items-center justify-center rounded-full border {{ $question->correct_answer === chr(65 + $optIndex) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30' : 'border-slate-300 dark:border-slate-600' }} text-xs font-medium">
                                            {{ chr(65 + $optIndex) }}
                                        </span>
                                        {{ $option }}
                                        @if($question->correct_answer === chr(65 + $optIndex))
                                            <x-ui.icon name="o-check-circle" class="w-4 h-4 text-emerald-500" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-1 ml-4">
                        <x-ui.button icon="o-pencil-square" wire:click="edit({{ $question->id }})" ghost />
                        <x-ui.button
                            icon="o-trash"
                            class="text-red-600 dark:text-red-400"
                            wire:confirm="{{ __('Hapus soal ini?') }}"
                            wire:click="delete({{ $question->id }})"
                            ghost
                        />
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card shadow>
                <div class="text-center py-8 text-slate-500">
                    <x-ui.icon name="o-document-text" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                    <p>{{ __('Belum ada soal. Klik "Tambah Soal" untuk memulai.') }}</p>
                </div>
            </x-ui.card>
        @endforelse
    </div>

    {{-- Question Modal --}}
    <x-ui.modal wire:model="questionModal" persistent class="max-w-3xl">
        <x-ui.header :title="$editing ? __('Edit Soal') : __('Tambah Soal Baru')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select
                    wire:model.live="question_type"
                    :label="__('Tipe Soal')"
                    :options="[
                        ['id' => 'multiple_choice', 'name' => __('Pilihan Ganda')],
                        ['id' => 'essay', 'name' => __('Essay')],
                        ['id' => 'file_upload', 'name' => __('Upload File')],
                    ]"
                    option-label="name"
                    required
                />
                <x-ui.input wire:model="points" :label="__('Poin')" type="number" min="1" required />
                <x-ui.input wire:model="order" :label="__('Urutan')" type="number" min="0" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Pertanyaan') }}</label>
                <textarea wire:model="question_text" rows="4" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" placeholder="{{ __('Tulis pertanyaan di sini...') }}" required></textarea>
            </div>

            @if($question_type === 'multiple_choice')
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Opsi Jawaban') }}</label>
                    @foreach($options as $index => $option)
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300">
                                {{ chr(65 + $loop->index) }}
                            </span>
                            <x-ui.input wire:model="options.{{ $index }}" :placeholder="__('Opsi ') . chr(65 + $loop->index)" class="flex-1" required />
                            @if(count($options) > 2)
                                <x-ui.button icon="o-x-mark" wire:click="removeOption({{ $loop->index }})" ghost class="text-red-500" />
                            @endif
                        </div>
                    @endforeach
                    <x-ui.button :label="__('Tambah Opsi')" icon="o-plus" wire:click="addOption" ghost class="text-emerald-600" />

                    <x-ui.select
                        wire:model="correct_answer"
                        :label="__('Jawaban Benar')"
                        :options="collect($options)->values()->map(fn ($o, $i) => ['id' => chr(65 + $i), 'name' => chr(65 + $i) . '. ' . ($o ?: '...')])->toArray()"
                        option-label="name"
                        required
                    />
                </div>
            @endif

            @if($question_type === 'essay')
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                    <x-ui.icon name="o-information-circle" class="w-4 h-4 inline mr-1" />
                    {{ __('Soal essay akan dikoreksi secara manual oleh admin/guru.') }}
                </div>
            @endif

            @if($question_type === 'file_upload')
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                    <x-ui.icon name="o-information-circle" class="w-4 h-4 inline mr-1" />
                    {{ __('Siswa akan mengupload file sebagai jawaban. Koreksi dilakukan manual.') }}
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
