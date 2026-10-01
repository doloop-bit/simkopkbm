<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\OnlineMaterial;
use App\Models\Subject;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public ?int $filterClassroom = null;
    public ?int $filterSubject = null;

    // Form fields
    public bool $materialModal = false;
    public ?OnlineMaterial $editing = null;
    public ?int $subject_id = null;
    public ?int $classroom_id = null;
    public ?int $academic_year_id = null;
    public string $semester = '1';
    public string $title = '';
    public string $content = '';
    public bool $is_published = false;
    public int $order = 0;
    public $uploadFiles = [];

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester' => ['required', 'in:1,2'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'order' => ['integer', 'min:0'],
            'uploadFiles.*' => ['nullable', 'file', 'max:51200'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->reset(['subject_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'content', 'is_published', 'order', 'editing', 'uploadFiles']);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academic_year_id = $activeYear?->id;
        $this->materialModal = true;
    }

    public function edit(OnlineMaterial $material): void
    {
        $this->editing = $material;
        $this->subject_id = $material->subject_id;
        $this->classroom_id = $material->classroom_id;
        $this->academic_year_id = $material->academic_year_id;
        $this->semester = $material->semester;
        $this->title = $material->title;
        $this->content = $material->content ?? '';
        $this->is_published = $material->is_published;
        $this->order = $material->order;
        $this->uploadFiles = [];
        $this->materialModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $attachments = $this->editing?->attachments ?? [];

        if (!empty($this->uploadFiles)) {
            foreach ($this->uploadFiles as $file) {
                $path = $file->store('elearning/materials', 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $data = [
            'subject_id' => $this->subject_id,
            'classroom_id' => $this->classroom_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'title' => $this->title,
            'content' => $this->content,
            'attachments' => $attachments,
            'is_published' => $this->is_published,
            'published_at' => $this->is_published ? now() : null,
            'order' => $this->order,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['created_by'] = auth()->id();
            OnlineMaterial::create($data);
        }

        $this->reset(['subject_id', 'classroom_id', 'academic_year_id', 'semester', 'title', 'content', 'is_published', 'order', 'editing', 'uploadFiles']);
        $this->materialModal = false;
    }

    public function removeAttachment(int $materialId, int $index): void
    {
        $material = OnlineMaterial::findOrFail($materialId);
        $attachments = $material->attachments ?? [];

        if (isset($attachments[$index])) {
            Storage::disk('public')->delete($attachments[$index]['path']);
            array_splice($attachments, $index, 1);
            $material->update(['attachments' => $attachments]);
        }
    }

    public function togglePublish(OnlineMaterial $material): void
    {
        $material->update([
            'is_published' => !$material->is_published,
            'published_at' => !$material->is_published ? now() : null,
        ]);
    }

    public function delete(OnlineMaterial $material): void
    {
        if ($material->attachments) {
            foreach ($material->attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }
        }
        $material->delete();
    }

    public function with(): array
    {
        $query = OnlineMaterial::query()
            ->with(['subject', 'classroom', 'academicYear', 'creator'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterClassroom, fn ($q) => $q->where('classroom_id', $this->filterClassroom))
            ->when($this->filterSubject, fn ($q) => $q->where('subject_id', $this->filterSubject))
            ->latest();

        return [
            'materials' => $query->paginate(10),
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
    <x-ui.header :title="__('Materi Online')" :subtitle="__('Kelola materi pembelajaran untuk siswa daring.')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Materi')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Filters --}}
    <x-ui.card shadow>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input wire:model.live.debounce.300ms="search" :placeholder="__('Cari materi...')" icon="o-magnifying-glass" />
            <x-ui.select wire:model.live="filterClassroom" :placeholder="__('Semua Kelas')" :options="$classrooms" option-label="name" />
            <x-ui.select wire:model.live="filterSubject" :placeholder="__('Semua Mata Pelajaran')" :options="$subjects" option-label="name" />
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'title', 'label' => __('Judul Materi')],
                ['key' => 'subject', 'label' => __('Mata Pelajaran')],
                ['key' => 'classroom', 'label' => __('Kelas')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'attachments_count', 'label' => __('File')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ]"
            :rows="$materials"
        >
            @scope('cell_title', $material)
                <div>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $material->title }}</span>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit(strip_tags($material->content), 60) }}</div>
                </div>
            @endscope

            @scope('cell_subject', $material)
                <span class="text-sm">{{ $material->subject?->name }}</span>
            @endscope

            @scope('cell_classroom', $material)
                <span class="text-sm">{{ $material->classroom?->name }}</span>
            @endscope

            @scope('cell_status', $material)
                @if($material->is_published)
                    <x-ui.badge :label="__('Diterbitkan')" variant="success" flat size="xs" />
                @else
                    <x-ui.badge :label="__('Draft')" variant="amber" flat size="xs" />
                @endif
            @endscope

            @scope('cell_attachments_count', $material)
                @if($material->attachments && count($material->attachments) > 0)
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ count($material->attachments) }} file</span>
                @else
                    <span class="text-sm text-slate-400">-</span>
                @endif
            @endscope

            @scope('cell_actions', $material)
                <div class="flex justify-end gap-1">
                    <x-ui.button
                        :icon="$material->is_published ? 'o-eye-slash' : 'o-eye'"
                        wire:click="togglePublish({{ $material->id }})"
                        ghost
                        :title="$material->is_published ? __('Sembunyikan') : __('Terbitkan')"
                    />
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $material->id }})" ghost />
                    <x-ui.button
                        icon="o-trash"
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                        wire:confirm="{{ __('Yakin ingin menghapus materi ini?') }}"
                        wire:click="delete({{ $material->id }})"
                        ghost
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>

    {{-- Modal --}}
    <x-ui.modal wire:model="materialModal" persistent class="max-w-3xl">
        <x-ui.header :title="$editing ? __('Edit Materi') : __('Tambah Materi Baru')" :subtitle="__('Masukkan detail materi pembelajaran.')" separator />

        <form wire:submit="save" class="space-y-6">
            <x-ui.input wire:model="title" :label="__('Judul Materi')" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.select wire:model="classroom_id" :label="__('Kelas')" :options="$classrooms" option-label="name" required />
                <x-ui.select wire:model="subject_id" :label="__('Mata Pelajaran')" :options="$subjects" option-label="name" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select wire:model="academic_year_id" :label="__('Tahun Ajaran')" :options="$academicYears" option-label="name" required />
                <x-ui.select
                    wire:model="semester"
                    :label="__('Semester')"
                    :options="[['id' => '1', 'name' => __('Semester 1')], ['id' => '2', 'name' => __('Semester 2')]]"
                    option-label="name"
                    required
                />
                <x-ui.input wire:model="order" :label="__('Urutan')" type="number" min="0" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Konten Materi') }}</label>
                <textarea wire:model="content" rows="8" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" placeholder="{{ __('Tulis konten materi di sini...') }}"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Upload File (PDF, Gambar, Video)') }}</label>
                <input type="file" wire:model="uploadFiles" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/30 dark:file:text-emerald-400" />
                <div wire:loading wire:target="uploadFiles" class="text-sm text-emerald-600 mt-1">{{ __('Mengupload file...') }}</div>
            </div>

            @if($editing && $editing->attachments)
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('File Terlampir') }}</label>
                    @foreach($editing->attachments as $index => $attachment)
                        <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-800 rounded-lg">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="o-paper-clip" class="w-4 h-4 text-slate-500" />
                                <span class="text-sm">{{ $attachment['name'] }}</span>
                                <span class="text-xs text-slate-400">({{ number_format($attachment['size'] / 1024, 1) }} KB)</span>
                            </div>
                            <x-ui.button
                                icon="o-x-mark"
                                wire:click="removeAttachment({{ $editing->id }}, {{ $index }})"
                                wire:confirm="{{ __('Hapus file ini?') }}"
                                ghost
                                class="text-red-500"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            <x-ui.checkbox wire:model="is_published" :label="__('Terbitkan sekarang')" />

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>
</div>
