<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\MaterialChapter;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $filterClassroom = null;
    public ?int $filterSubject = null;

    // Form fields
    public bool $chapterModal = false;
    public ?MaterialChapter $editing = null;
    public ?int $subject_id = null;
    public ?int $classroom_id = null;
    public ?int $academic_year_id = null;
    public string $semester = 'Ganjil';
    public string $title = '';
    public string $description = '';
    public bool $is_published = true;
    public int $order = 1;

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'order' => ['integer', 'min:1'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->reset(['subject_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'description', 'is_published', 'order', 'editing']);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
        $this->semester = $activeYear?->active_semester ?? 'Ganjil';
        $this->chapterModal = true;
    }

    public function edit(MaterialChapter $chapter): void
    {
        $this->editing = $chapter;
        $this->subject_id = $chapter->subject_id;
        $this->classroom_id = $chapter->classroom_id;
        $this->academic_year_id = $chapter->academic_year_id;
        $this->semester = $chapter->semester;
        $this->title = $chapter->title;
        $this->description = $chapter->description ?? '';
        $this->is_published = $chapter->is_published;
        $this->order = $chapter->order;
        $this->chapterModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'subject_id' => $this->subject_id,
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'title' => $this->title,
            'description' => $this->description,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published ? now() : null,
            'order' => $this->order,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['created_by'] = auth()->id();
            MaterialChapter::create($data);
        }

        $this->reset(['subject_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'description', 'is_published', 'order', 'editing']);
        $this->chapterModal = false;
    }

    public function togglePublish(MaterialChapter $chapter): void
    {
        $chapter->update([
            'is_published' => !$chapter->is_published,
            'published_at' => !$chapter->is_published ? now() : null,
        ]);
    }

    public function delete(MaterialChapter $chapter): void
    {
        $chapter->delete();
    }

    public function with(): array
    {
        $query = MaterialChapter::query()
            ->with(['subject', 'classroom', 'academicYear', 'creator'])
            ->withCount(['materials', 'exams'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterClassroom, fn ($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterSubject, fn ($q) => $q->where('subject_id', $this->filterSubject))
            ->orderBy('order');

        return [
            'chapters' => $query->paginate(10),
            'classrooms' => Classroom::whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                ->with('level')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => ($c->level?->name ?? '') . ' - ' . $c->name]),
            'subjects' => Subject::orderBy('name')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'academicYears' => AcademicYear::latest()->get()->map(fn ($y) => ['id' => $y->id, 'name' => $y->name]),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Modul / Bab Pembelajaran')" :subtitle="__('Kelola kelompok modul/bab per mata pelajaran dan kelas.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Bab')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Filters --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari bab...')" icon="o-magnifying-glass" />
            <x-ui.select wire:model.live="filterClassroom" :placeholder="__('Semua Kelas')" :options="$classrooms" option-label="name" />
            <x-ui.select wire:model.live="filterSubject" :placeholder="__('Semua Mata Pelajaran')" :options="$subjects" option-label="name" />
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'order', 'label' => __('Urutan')],
                ['key' => 'title', 'label' => __('Nama Bab / Modul')],
                ['key' => 'subject', 'label' => __('Mata Pelajaran')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'semester', 'label' => __('Semester')],
                ['key' => 'items_count', 'label' => __('Materi / Kuis')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ]"
            :rows="$chapters"
        >
            @scope('cell_order', $chapter)
                <span class="font-bold text-slate-700 dark:text-slate-300">Bab {{ $chapter->order }}</span>
            @endscope

            @scope('cell_title', $chapter)
                <div>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $chapter->title }}</span>
                    @if($chapter->description)
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($chapter->description, 60) }}</div>
                    @endif
                </div>
            @endscope

            @scope('cell_subject', $chapter)
                <span class="text-sm">{{ $chapter->subject?->name }}</span>
            @endscope

            @scope('cell_classroom', $chapter)
                <span class="text-sm">{{ $chapter->classroom?->name }}</span>
            @endscope

            @scope('cell_semester', $chapter)
                <x-ui.badge :label="$chapter->semester" flat size="xs" />
            @endscope

            @scope('cell_items_count', $chapter)
                <div class="text-xs space-x-1">
                    <x-ui.badge :label="$chapter->materials_count . ' materi'" flat size="xs" variant="info" />
                    <x-ui.badge :label="$chapter->exams_count . ' kuis'" flat size="xs" variant="warning" />
                </div>
            @endscope

            @scope('cell_status', $chapter)
                @if($chapter->is_published)
                    <x-ui.badge :label="__('Diterbitkan')" variant="success" flat size="xs" />
                @else
                    <x-ui.badge :label="__('Draft')" variant="amber" flat size="xs" />
                @endif
            @endscope

            @scope('cell_actions', $chapter)
                <div class="flex justify-end gap-1">
                    <x-ui.button
                        :icon="$chapter->is_published ? 'o-eye-slash' : 'o-eye'"
                        wire:click="togglePublish({{ $chapter->id }})"
                        ghost
                        :title="$chapter->is_published ? __('Sembunyikan') : __('Terbitkan')"
                    />
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $chapter->id }})" ghost />
                    <x-ui.button
                        icon="o-trash"
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                        wire:confirm="{{ __('Yakin ingin menghapus bab ini beserta materi di dalamnya?') }}"
                        wire:click="delete({{ $chapter->id }})"
                        ghost
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $chapters->links() }}
    </div>

    {{-- Modal --}}
    <x-ui.modal wire:model="chapterModal" persistent class="max-w-2xl">
        <x-ui.header :title="$editing ? __('Edit Bab') : __('Tambah Bab Baru')" :subtitle="__('Masukkan detail modul/bab pembelajaran.')" separator />

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input wire:model="title" :label="__('Judul Bab / Modul')" placeholder="misal: Bab 1 - Pengenalan Aljabar" required />
                <x-ui.input wire:model="order" :label="__('Urutan Bab')" type="number" min="1" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select wire:model="classroom_id" :label="__('Kelas')" :options="$classrooms" option-label="name" required />
                <x-ui.select wire:model="subject_id" :label="__('Mata Pelajaran')" :options="$subjects" option-label="name" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :options="$academicYears" option-label="name" required />
                <x-ui.select
                    wire:model="semester"
                    :label="__('Semester')"
                    :options="[['id' => 'Ganjil', 'name' => __('Semester Ganjil')], ['id' => 'Genap', 'name' => __('Semester Genap')]]"
                    option-label="name"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Deskripsi Bab') }}</label>
                <textarea wire:model="description" rows="4" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" placeholder="{{ __('Ringkasan apa yang dipelajari pada bab ini...') }}"></textarea>
            </div>

            <x-ui.checkbox wire:model="is_published" :label="__('Terbitkan sekarang')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
