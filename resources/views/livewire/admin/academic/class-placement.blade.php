<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public string $activeTab = 'placement';

    // ── Placement tab ─────────────────────────────────────────────
    public ?int $academic_year_id = null;
    public ?int $level_id = null;
    public ?string $source_classroom_id = null;
    public ?string $target_classroom_id = null;

    public array $sourceStudents = [];
    public array $targetStudents = [];

    public string $sourceSearch = '';
    public string $targetSearch = '';

    // ── Promotion tab ─────────────────────────────────────────────
    public ?int $promo_source_year_id = null;
    public ?int $promo_source_level_id = null;
    public ?string $promo_source_classroom_id = null;
    public ?int $promo_target_year_id = null;
    public ?int $promo_target_level_id = null;
    public ?string $promo_target_classroom_id = null;
    public array $promoStudents = [];
    public array $promoSourceClassrooms = [];
    public array $promoTargetClassrooms = [];

    // ── Graduation tab ────────────────────────────────────────────
    public ?int $grad_year_id = null;
    public ?int $grad_level_id = null;
    public ?string $grad_classroom_id = null;
    public array $gradStudents = [];
    public array $gradClassrooms = [];

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
            $this->promo_target_year_id = $activeYear->id;
            $this->grad_year_id = $activeYear->id;
        }
    }

    // ── Placement ─────────────────────────────────────────────────

    public function updatedLevelId(): void
    {
        $this->source_classroom_id = null;
        $this->target_classroom_id = null;
        $this->sourceStudents = [];
        $this->targetStudents = [];
        $this->sourceSearch = '';
        $this->targetSearch = '';
    }

    public function updatedSourceClassroomId(): void
    {
        $this->loadSourceStudents();
    }

    public function updatedTargetClassroomId(): void
    {
        $this->loadTargetStudents();
    }

    public function updatedSourceSearch(): void
    {
        $this->loadSourceStudents();
    }

    public function updatedTargetSearch(): void
    {
        $this->loadTargetStudents();
    }

    public function loadSourceStudents(): void
    {
        $query = StudentProfile::with(['profile.user']);

        if ($this->source_classroom_id === 'unassigned') {
            $query->whereNull('classroom_id');
        } elseif ($this->source_classroom_id) {
            $query->where('classroom_id', $this->source_classroom_id);
        } else {
            $this->sourceStudents = [];
            return;
        }

        if ($this->sourceSearch) {
            $query->where(function ($q) {
                $q->where('nis', 'like', '%'.$this->sourceSearch.'%')
                    ->orWhereHas('profile.user', function ($q) {
                        $q->where('name', 'like', '%'.$this->sourceSearch.'%');
                    });
            });
        }

        $this->sourceStudents = $query->orderBy('nis')->get()->map(function ($sp) {
            return [
                'id' => $sp->id,
                'name' => $sp->profile?->user?->name ?? 'Tanpa Nama',
                'nis' => $sp->nis,
            ];
        })->toArray();
    }

    public function loadTargetStudents(): void
    {
        $query = StudentProfile::with(['profile.user']);

        if ($this->target_classroom_id === 'unassigned') {
            $query->whereNull('classroom_id');
        } elseif ($this->target_classroom_id) {
            $query->where('classroom_id', $this->target_classroom_id);
        } else {
            $this->targetStudents = [];
            return;
        }

        if ($this->targetSearch) {
            $query->where(function ($q) {
                $q->where('nis', 'like', '%'.$this->targetSearch.'%')
                    ->orWhereHas('profile.user', function ($q) {
                        $q->where('name', 'like', '%'.$this->targetSearch.'%');
                    });
            });
        }

        $this->targetStudents = $query->orderBy('nis')->get()->map(function ($sp) {
            return [
                'id' => $sp->id,
                'name' => $sp->profile?->user?->name ?? 'Tanpa Nama',
                'nis' => $sp->nis,
            ];
        })->toArray();
    }

    public function moveStudents(array $studentIds, $targetClassroomId): void
    {
        if (empty($studentIds)) {
            session()->flash('error', 'Tidak ada siswa yang dipilih');
            return;
        }

        $targetId = $targetClassroomId === 'unassigned' ? null : (int) $targetClassroomId;

        DB::transaction(function () use ($studentIds, $targetId) {
            StudentProfile::whereIn('id', $studentIds)
                ->update(['classroom_id' => $targetId]);
        });

        $this->loadSourceStudents();
        $this->loadTargetStudents();

        $count = count($studentIds);
        session()->flash('success', "$count siswa berhasil dipindahkan.");
    }

    public function moveStudentsFromAlpine(array $studentIds, $newTargetId): void
    {
        if (! $newTargetId) {
            session()->flash('error', 'Pilih kelas tujuan terlebih dahulu');
            return;
        }
        $this->moveStudents($studentIds, $newTargetId);
    }

    // ── Promotion ─────────────────────────────────────────────────

    public function updatedPromoSourceLevelId(): void
    {
        $this->promo_source_classroom_id = null;
        $this->promoStudents = [];
        $this->loadPromoSourceClassrooms();
    }

    public function updatedPromoSourceYearId(): void
    {
        $this->promo_source_classroom_id = null;
        $this->promoStudents = [];
        $this->loadPromoSourceClassrooms();
    }

    public function updatedPromoTargetLevelId(): void
    {
        $this->promo_target_classroom_id = null;
        $this->loadPromoTargetClassrooms();
    }

    public function updatedPromoTargetYearId(): void
    {
        $this->promo_target_classroom_id = null;
        $this->loadPromoTargetClassrooms();
    }

    public function updatedPromoSourceClassroomId(): void
    {
        $this->loadPromoStudents();
    }

    public function loadPromoSourceClassrooms(): void
    {
        if (! $this->promo_source_year_id || ! $this->promo_source_level_id) {
            $this->promoSourceClassrooms = [];
            return;
        }
        $this->promoSourceClassrooms = Classroom::where('academic_year_id', $this->promo_source_year_id)
            ->where('level_id', $this->promo_source_level_id)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    public function loadPromoTargetClassrooms(): void
    {
        if (! $this->promo_target_year_id || ! $this->promo_target_level_id) {
            $this->promoTargetClassrooms = [];
            return;
        }
        $this->promoTargetClassrooms = Classroom::where('academic_year_id', $this->promo_target_year_id)
            ->where('level_id', $this->promo_target_level_id)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    public function loadPromoStudents(): void
    {
        if (! $this->promo_source_classroom_id) {
            $this->promoStudents = [];
            return;
        }

        $this->promoStudents = StudentProfile::with(['profile.user'])
            ->where('classroom_id', $this->promo_source_classroom_id)
            ->orderBy('nis')
            ->get()
            ->map(fn ($sp) => [
                'id' => $sp->id,
                'name' => $sp->profile?->user?->name ?? 'Tanpa Nama',
                'nis' => $sp->nis,
            ])
            ->toArray();
    }

    public function promoteStudents(array $studentIds): void
    {
        if (empty($studentIds)) {
            session()->flash('error', 'Pilih minimal satu siswa.');
            return;
        }

        if (! $this->promo_target_classroom_id) {
            session()->flash('error', 'Pilih kelas tujuan terlebih dahulu.');
            return;
        }

        $targetId = (int) $this->promo_target_classroom_id;

        DB::transaction(function () use ($studentIds, $targetId) {
            StudentProfile::whereIn('id', $studentIds)
                ->update([
                    'classroom_id' => $targetId,
                    'status' => 'naik_kelas',
                ]);
        });

        $this->loadPromoStudents();

        $count = count($studentIds);
        session()->flash('success', "$count siswa berhasil dinaikkan ke kelas tujuan.");
    }

    // ── Graduation ────────────────────────────────────────────────

    public function updatedGradLevelId(): void
    {
        $this->grad_classroom_id = null;
        $this->gradStudents = [];
        $this->loadGradClassrooms();
    }

    public function updatedGradYearId(): void
    {
        $this->grad_classroom_id = null;
        $this->gradStudents = [];
        $this->loadGradClassrooms();
    }

    public function updatedGradClassroomId(): void
    {
        $this->loadGradStudents();
    }

    public function loadGradClassrooms(): void
    {
        if (! $this->grad_year_id || ! $this->grad_level_id) {
            $this->gradClassrooms = [];
            return;
        }
        $this->gradClassrooms = Classroom::where('academic_year_id', $this->grad_year_id)
            ->where('level_id', $this->grad_level_id)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    public function loadGradStudents(): void
    {
        if (! $this->grad_classroom_id) {
            $this->gradStudents = [];
            return;
        }

        $this->gradStudents = StudentProfile::with(['profile.user'])
            ->where('classroom_id', $this->grad_classroom_id)
            ->orderBy('nis')
            ->get()
            ->map(fn ($sp) => [
                'id' => $sp->id,
                'name' => $sp->profile?->user?->name ?? 'Tanpa Nama',
                'nis' => $sp->nis,
            ])
            ->toArray();
    }

    public function graduateStudents(array $studentIds): void
    {
        if (empty($studentIds)) {
            session()->flash('error', 'Pilih minimal satu siswa.');
            return;
        }

        DB::transaction(function () use ($studentIds) {
            StudentProfile::whereIn('id', $studentIds)
                ->update([
                    'classroom_id' => null,
                    'status' => 'lulus',
                ]);
        });

        $this->loadGradStudents();

        $count = count($studentIds);
        session()->flash('success', "$count siswa berhasil diluluskan.");
    }

    public function with(): array
    {
        $classrooms = [];
        if ($this->academic_year_id && $this->level_id) {
            $classrooms = Classroom::where('academic_year_id', $this->academic_year_id)
                ->where('level_id', $this->level_id)
                ->orderBy('name')
                ->get();
        }

        return [
            'years' => AcademicYear::all(),
            'levels' => Level::all(),
            'classrooms' => $classrooms,
        ];
    }
}; ?>

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Penempatan Kelas')" :subtitle="__('Pindahkan siswa antar kelas, naikkan kelas, atau luluskan secara massal.')" separator />

    @if (session('success'))
        <x-ui.alert :title="__('Berhasil')" icon="o-check-circle" class="bg-emerald-50 text-emerald-800 border-emerald-100" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert :title="__('Error')" icon="o-exclamation-triangle" class="bg-rose-50 text-rose-800 border-rose-100" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    {{-- Tab Nav --}}
    <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800/50 rounded-2xl w-full md:w-fit">
        <button
            wire:click="$set('activeTab', 'placement')"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 {{ $activeTab === 'placement' ? 'bg-white dark:bg-slate-900 shadow text-primary' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <x-ui.icon name="o-arrows-right-left" class="size-4" />
            {{ __('Penempatan & Mutasi') }}
        </button>
        <button
            wire:click="$set('activeTab', 'promotion')"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 {{ $activeTab === 'promotion' ? 'bg-white dark:bg-slate-900 shadow text-primary' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <x-ui.icon name="o-arrow-trending-up" class="size-4" />
            {{ __('Kenaikan Kelas') }}
        </button>
        <button
            wire:click="$set('activeTab', 'graduation')"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 {{ $activeTab === 'graduation' ? 'bg-white dark:bg-slate-900 shadow text-emerald-600' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <x-ui.icon name="o-academic-cap" class="size-4" />
            {{ __('Kelulusan') }}
        </button>
    </div>

    {{-- ================================================================ --}}
    {{-- TAB 1: Penempatan & Mutasi                                        --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'placement')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <x-ui.select wire:model.live="academic_year_id" :label="__('Tahun Ajaran')" :options="$years" :placeholder="__('Pilih Tahun')" />
            <x-ui.select wire:model.live="level_id" :label="__('Jenjang')" :options="$levels" :placeholder="__('Pilih Jenjang')" />
        </div>

        @if($academic_year_id && $level_id)
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:gap-12"
                 x-data="{
                    selectedSource: [],
                    selectedTarget: [],
                    draggingSource: false,
                    draggingTarget: false,
                    toggleSource(id) {
                        id = id.toString();
                        if (this.selectedSource.includes(id)) {
                            this.selectedSource = this.selectedSource.filter(i => i !== id);
                        } else {
                            this.selectedSource.push(id);
                        }
                    },
                    toggleTarget(id) {
                        id = id.toString();
                        if (this.selectedTarget.includes(id)) {
                            this.selectedTarget = this.selectedTarget.filter(i => i !== id);
                        } else {
                            this.selectedTarget.push(id);
                        }
                    },
                    selectAllSource(ids) {
                        if (this.selectedSource.length === ids.length) {
                            this.selectedSource = [];
                        } else {
                            this.selectedSource = ids.map(id => id.toString());
                        }
                    },
                    selectAllTarget(ids) {
                        if (this.selectedTarget.length === ids.length) {
                            this.selectedTarget = [];
                        } else {
                            this.selectedTarget = ids.map(id => id.toString());
                        }
                    },
                    getDragDataSource(id) {
                        id = id.toString();
                        if(!this.selectedSource.includes(id)) this.selectedSource = [id];
                        return JSON.stringify({ source: 'source', ids: this.selectedSource });
                    },
                    getDragDataTarget(id) {
                        id = id.toString();
                        if(!this.selectedTarget.includes(id)) this.selectedTarget = [id];
                        return JSON.stringify({ source: 'target', ids: this.selectedTarget });
                    },
                    onDropTarget(e) {
                        this.draggingTarget = false;
                        let data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
                        if (data.source === 'source' && data.ids && data.ids.length > 0) {
                            $wire.moveStudentsFromAlpine(data.ids, $wire.target_classroom_id);
                            this.selectedSource = [];
                        }
                    },
                    onDropSource(e) {
                        this.draggingSource = false;
                        let data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
                        if (data.source === 'target' && data.ids && data.ids.length > 0) {
                            $wire.moveStudentsFromAlpine(data.ids, $wire.source_classroom_id);
                            this.selectedTarget = [];
                        }
                    },
                    moveToTarget() {
                        if(this.selectedSource.length === 0) return;
                        $wire.moveStudentsFromAlpine(this.selectedSource, $wire.target_classroom_id);
                        this.selectedSource = [];
                    },
                    moveToSource() {
                        if(this.selectedTarget.length === 0) return;
                        $wire.moveStudentsFromAlpine(this.selectedTarget, $wire.source_classroom_id);
                        this.selectedTarget = [];
                    }
                 }">

                <!-- Panel Kiri (Sumber) -->
                <div class="flex flex-col space-y-4">
                    <x-ui.card shadow padding="false" class="overflow-hidden !bg-slate-50 dark:!bg-slate-800/30 border-slate-200 dark:border-slate-800">
                        <div class="p-5 space-y-5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                            <x-ui.select wire:model.live="source_classroom_id" :label="__('Kelas Asal')">
                                <option value="">{{ __('Pilih Kelas Asal / Status Baru') }}</option>
                                <option value="unassigned" class="font-bold text-primary">{{ __('- Belum Ada Kelas (Siswa Baru) -') }}</option>
                                @foreach($classrooms as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                wire:model.live.debounce.300ms="sourceSearch"
                                icon="o-magnifying-glass"
                                :placeholder="__('Cari Nama / NIS...')"
                                class="bg-white"
                            />

                            <div class="flex items-center justify-between">
                                <x-ui.checkbox
                                    x-on:click="selectAllSource({{ json_encode(array_column($sourceStudents, 'id')) }})"
                                    x-bind:checked="selectedSource.length > 0 && selectedSource.length === {{ count($sourceStudents) }}"
                                    :label="__('Pilih Semua (:count)', ['count' => count($sourceStudents)])" />

                                <x-ui.button x-show="selectedSource.length > 0" x-on:click="moveToTarget" class="btn-primary flex items-center gap-2">
                                    {{ __('Pindah Kanan') }} <x-ui.icon name="o-arrow-right" class="size-3" />
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="p-3 overflow-y-auto h-[600px] transition-all duration-300"
                             x-on:dragover.prevent="draggingSource = true"
                             x-on:dragleave.prevent="draggingSource = false"
                             x-on:drop.prevent="onDropSource($event)"
                             x-bind:class="draggingSource ? 'bg-primary/5 ring-4 ring-inset ring-primary/20' : ''">
                            <div class="space-y-2 pb-6">
                                @if(count($sourceStudents) === 0 && $source_classroom_id)
                                    <div class="py-24 text-center opacity-30 italic text-sm">
                                        {{ __('Tidak ada siswa di kelas ini.') }}
                                    </div>
                                @endif

                                @foreach($sourceStudents as $student)
                                    <div class="p-3 rounded-2xl border flex items-center bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 cursor-grab active:cursor-grabbing hover:border-primary transition-all group relative overflow-hidden"
                                         draggable="true"
                                         x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataSource({{ $student['id'] }}))"
                                         x-bind:class="selectedSource.includes('{{ $student['id'] }}') ? 'ring-2 ring-primary border-primary bg-primary/5' : ''"
                                         x-on:click="toggleSource({{ $student['id'] }})">

                                        <div class="absolute inset-y-0 left-0 w-1 bg-primary opacity-0 group-hover:opacity-100 transition-opacity"></div>

                                        <div class="size-8 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-primary/10 transition-colors">
                                            <x-ui.icon name="o-bars-3" class="size-4 opacity-20 group-hover:opacity-100 group-hover:text-primary transition-all" />
                                        </div>

                                        <div class="flex-1">
                                            <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $student['name'] }}</div>
                                            <div class="text-[10px] uppercase font-mono tracking-wider text-slate-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                        </div>

                                        <div x-on:click.stop class="ml-2">
                                            <x-ui.checkbox id="source-{{ $student['id'] }}" x-model="selectedSource" value="{{ $student['id'] }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <!-- Panel Kanan (Tujuan) -->
                <div class="flex flex-col space-y-4">
                    <x-ui.card shadow padding="false" class="overflow-hidden !bg-slate-50 dark:!bg-slate-800/30 border-slate-200 dark:border-slate-800">
                        <div class="p-5 space-y-5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                            <x-ui.select wire:model.live="target_classroom_id" :label="__('Kelas Tujuan')">
                                <option value="">{{ __('Pilih Kelas Tujuan') }}</option>
                                <option value="unassigned" class="font-bold text-rose-600">{{ __('- Belum Ada Kelas (Cabut Siswa) -') }}</option>
                                @foreach($classrooms as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                wire:model.live.debounce.300ms="targetSearch"
                                icon="o-magnifying-glass"
                                :placeholder="__('Cari Nama / NIS...')"
                                class="bg-white"
                            />

                            <div class="flex items-center justify-between">
                                <x-ui.checkbox
                                    x-on:click="selectAllTarget({{ json_encode(array_column($targetStudents, 'id')) }})"
                                    x-bind:checked="selectedTarget.length > 0 && selectedTarget.length === {{ count($targetStudents) }}"
                                    :label="__('Pilih Semua (:count)', ['count' => count($targetStudents)])" />

                                <x-ui.button x-show="selectedTarget.length > 0" x-on:click="moveToSource" class="btn-primary flex items-center gap-2">
                                    <x-ui.icon name="o-arrow-left" class="size-3" /> {{ __('Pindah Kiri') }}
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="p-3 overflow-y-auto h-[600px] transition-all duration-300"
                             x-on:dragover.prevent="draggingTarget = true"
                             x-on:dragleave.prevent="draggingTarget = false"
                             x-on:drop.prevent="onDropTarget($event)"
                             x-bind:class="draggingTarget ? 'bg-emerald-500/5 ring-4 ring-inset ring-emerald-500/20' : ''">
                            <div class="space-y-2 pb-6">
                                @if(count($targetStudents) === 0 && $target_classroom_id)
                                    <div class="py-24 text-center opacity-30 italic text-sm">
                                        {{ __('Pilih / Drag siswa ke sini.') }}
                                    </div>
                                @endif

                                @foreach($targetStudents as $student)
                                    <div class="p-3 rounded-2xl border flex items-center bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 cursor-grab active:cursor-grabbing hover:border-primary transition-all group relative overflow-hidden"
                                         draggable="true"
                                         x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataTarget({{ $student['id'] }}))"
                                         x-bind:class="selectedTarget.includes('{{ $student['id'] }}') ? 'ring-2 ring-primary border-primary bg-primary/5' : ''"
                                         x-on:click="toggleTarget({{ $student['id'] }})">

                                        <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                                        <div class="size-8 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-primary/10 transition-colors">
                                            <x-ui.icon name="o-bars-3" class="size-4 opacity-20 group-hover:opacity-100 group-hover:text-primary transition-all" />
                                        </div>

                                        <div class="flex-1">
                                            <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $student['name'] }}</div>
                                            <div class="text-[10px] uppercase font-mono tracking-wider text-slate-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                        </div>

                                        <div x-on:click.stop class="ml-2">
                                            <x-ui.checkbox id="target-{{ $student['id'] }}" x-model="selectedTarget" value="{{ $student['id'] }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                </div>

            </div>
        @else
            <div class="flex flex-col items-center justify-center py-40 rounded-[3rem] border-4 border-dashed border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all duration-500 group">
                <div class="size-24 rounded-[2rem] bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-8 group-hover:scale-110 group-hover:-rotate-3 transition-all ring-8 ring-slate-100/50 dark:ring-slate-800/30">
                    <x-ui.icon name="o-arrows-right-left" class="size-12 text-primary opacity-20" />
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">{{ __('Siap Pindah Kelas?') }}</h3>
                <p class="text-slate-400 dark:text-slate-500 text-base max-w-md text-center leading-relaxed">
                    {{ __('Pilih Tahun Ajaran dan Jenjang terlebih dahulu untuk mulai memindahkan siswa.') }}
                </p>
            </div>
        @endif
    @endif

    {{-- ================================================================ --}}
    {{-- TAB 2: Kenaikan Kelas                                            --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'promotion')
        <x-ui.alert icon="o-information-circle" class="bg-blue-50 dark:bg-blue-950/30 border-blue-100 dark:border-blue-900/50 text-blue-800 dark:text-blue-300">
            {{ __('Pilih kelas asal (tahun lalu) dan kelas tujuan (tahun ini). Siswa yang dipilih akan dipindahkan dan statusnya diubah menjadi "Naik Kelas".') }}
        </x-ui.alert>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2"
             x-data="{
                selectedPromo: [],
                togglePromo(id) {
                    id = id.toString();
                    if (this.selectedPromo.includes(id)) {
                        this.selectedPromo = this.selectedPromo.filter(i => i !== id);
                    } else {
                        this.selectedPromo.push(id);
                    }
                },
                selectAll(ids) {
                    if (this.selectedPromo.length === ids.length) {
                        this.selectedPromo = [];
                    } else {
                        this.selectedPromo = ids.map(id => id.toString());
                    }
                },
                promote() {
                    if (this.selectedPromo.length === 0) return;
                    $wire.promoteStudents(this.selectedPromo);
                    this.selectedPromo = [];
                }
             }">

            {{-- Panel Kiri: Kelas Asal --}}
            <x-ui.card shadow>
                <div class="space-y-5">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-400">{{ __('Kelas Asal (Tahun Ajaran Lama)') }}</div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.select
                            wire:model.live="promo_source_year_id"
                            :label="__('Tahun Ajaran Asal')"
                            :options="$years"
                            :placeholder="__('Pilih Tahun')"
                        />
                        <x-ui.select
                            wire:model.live="promo_source_level_id"
                            :label="__('Jenjang Asal')"
                            :options="$levels"
                            :placeholder="__('Pilih Jenjang')"
                        />
                    </div>

                    @if($promo_source_year_id && $promo_source_level_id)
                        <x-ui.select
                            wire:model.live="promo_source_classroom_id"
                            :label="__('Kelas Asal')"
                            :options="$promoSourceClassrooms"
                            :placeholder="__('Pilih Kelas')"
                        />
                    @endif

                    @if(count($promoStudents) > 0)
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                            <x-ui.checkbox
                                x-on:click="selectAll({{ json_encode(array_column($promoStudents, 'id')) }})"
                                x-bind:checked="selectedPromo.length > 0 && selectedPromo.length === {{ count($promoStudents) }}"
                                :label="__('Pilih Semua (:count siswa)', ['count' => count($promoStudents)])"
                            />
                        </div>

                        <div class="space-y-2 max-h-[450px] overflow-y-auto">
                            @foreach($promoStudents as $student)
                                <div
                                    class="p-3 rounded-2xl border flex items-center bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800 cursor-pointer hover:border-primary transition-all group"
                                    x-bind:class="selectedPromo.includes('{{ $student['id'] }}') ? 'ring-2 ring-primary border-primary bg-primary/5 dark:bg-primary/10' : ''"
                                    x-on:click="togglePromo({{ $student['id'] }})"
                                >
                                    <div class="flex-1">
                                        <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $student['name'] }}</div>
                                        <div class="text-[10px] uppercase font-mono tracking-wider text-slate-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                    </div>
                                    <div x-on:click.stop class="ml-2">
                                        <x-ui.checkbox x-model="selectedPromo" value="{{ $student['id'] }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($promo_source_classroom_id)
                        <div class="py-12 text-center opacity-30 italic text-sm">{{ __('Tidak ada siswa di kelas ini.') }}</div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Panel Kanan: Kelas Tujuan + Aksi --}}
            <x-ui.card shadow>
                <div class="space-y-5">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-400">{{ __('Kelas Tujuan (Tahun Ajaran Baru)') }}</div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.select
                            wire:model.live="promo_target_year_id"
                            :label="__('Tahun Ajaran Tujuan')"
                            :options="$years"
                            :placeholder="__('Pilih Tahun')"
                        />
                        <x-ui.select
                            wire:model.live="promo_target_level_id"
                            :label="__('Jenjang Tujuan')"
                            :options="$levels"
                            :placeholder="__('Pilih Jenjang')"
                        />
                    </div>

                    @if($promo_target_year_id && $promo_target_level_id)
                        <x-ui.select
                            wire:model.live="promo_target_classroom_id"
                            :label="__('Kelas Tujuan')"
                            :options="$promoTargetClassrooms"
                            :placeholder="__('Pilih Kelas')"
                        />
                    @endif

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div
                            x-show="selectedPromo.length > 0 && $wire.promo_target_classroom_id"
                            class="p-4 rounded-2xl bg-primary/5 border border-primary/20 space-y-3"
                        >
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                {{ __('Siap Menaikkan Kelas') }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <span x-text="selectedPromo.length"></span> {{ __('siswa dipilih → akan dipindahkan ke kelas tujuan dengan status "Naik Kelas"') }}
                            </div>
                            <x-ui.button
                                x-on:click="promote()"
                                wire:loading.attr="disabled"
                                class="btn-primary w-full flex items-center justify-center gap-2"
                                spinner="promoteStudents"
                            >
                                <x-ui.icon name="o-arrow-trending-up" class="size-4" />
                                {{ __('Naikkan Kelas Sekarang') }}
                            </x-ui.button>
                        </div>

                        <div x-show="selectedPromo.length === 0 || !$wire.promo_target_classroom_id" class="py-12 text-center opacity-30 italic text-sm">
                            {{ __('Pilih siswa dan kelas tujuan untuk menaikkan kelas.') }}
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- TAB 3: Kelulusan                                                  --}}
    {{-- ================================================================ --}}
    @if($activeTab === 'graduation')
        <x-ui.alert icon="o-exclamation-triangle" class="bg-amber-50 dark:bg-amber-950/30 border-amber-100 dark:border-amber-900/50 text-amber-800 dark:text-amber-300">
            {{ __('Tindakan ini akan menghapus penempatan kelas siswa dan mengubah statusnya menjadi "Lulus". Tindakan ini tidak bisa dibatalkan secara otomatis.') }}
        </x-ui.alert>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2"
             x-data="{
                selectedGrad: [],
                toggleGrad(id) {
                    id = id.toString();
                    if (this.selectedGrad.includes(id)) {
                        this.selectedGrad = this.selectedGrad.filter(i => i !== id);
                    } else {
                        this.selectedGrad.push(id);
                    }
                },
                selectAll(ids) {
                    if (this.selectedGrad.length === ids.length) {
                        this.selectedGrad = [];
                    } else {
                        this.selectedGrad = ids.map(id => id.toString());
                    }
                },
                graduate() {
                    if (this.selectedGrad.length === 0) return;
                    if (!confirm('Yakin ingin meluluskan ' + this.selectedGrad.length + ' siswa? Status akan diubah menjadi Lulus dan kelas akan dikosongkan.')) return;
                    $wire.graduateStudents(this.selectedGrad);
                    this.selectedGrad = [];
                }
             }">

            {{-- Panel Kiri: Pilih Kelas --}}
            <x-ui.card shadow>
                <div class="space-y-5">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-400">{{ __('Pilih Kelas yang Akan Diluluskan') }}</div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.select
                            wire:model.live="grad_year_id"
                            :label="__('Tahun Ajaran')"
                            :options="$years"
                            :placeholder="__('Pilih Tahun')"
                        />
                        <x-ui.select
                            wire:model.live="grad_level_id"
                            :label="__('Jenjang')"
                            :options="$levels"
                            :placeholder="__('Pilih Jenjang')"
                        />
                    </div>

                    @if($grad_year_id && $grad_level_id)
                        <x-ui.select
                            wire:model.live="grad_classroom_id"
                            :label="__('Kelas')"
                            :options="$gradClassrooms"
                            :placeholder="__('Pilih Kelas')"
                        />
                    @endif

                    @if(count($gradStudents) > 0)
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                            <x-ui.checkbox
                                x-on:click="selectAll({{ json_encode(array_column($gradStudents, 'id')) }})"
                                x-bind:checked="selectedGrad.length > 0 && selectedGrad.length === {{ count($gradStudents) }}"
                                :label="__('Pilih Semua (:count siswa)', ['count' => count($gradStudents)])"
                            />
                        </div>

                        <div class="space-y-2 max-h-[450px] overflow-y-auto">
                            @foreach($gradStudents as $student)
                                <div
                                    class="p-3 rounded-2xl border flex items-center bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800 cursor-pointer hover:border-emerald-400 transition-all group"
                                    x-bind:class="selectedGrad.includes('{{ $student['id'] }}') ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-500/5 dark:bg-emerald-500/10' : ''"
                                    x-on:click="toggleGrad({{ $student['id'] }})"
                                >
                                    <div class="flex-1">
                                        <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $student['name'] }}</div>
                                        <div class="text-[10px] uppercase font-mono tracking-wider text-slate-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                    </div>
                                    <div x-on:click.stop class="ml-2">
                                        <x-ui.checkbox x-model="selectedGrad" value="{{ $student['id'] }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($grad_classroom_id)
                        <div class="py-12 text-center opacity-30 italic text-sm">{{ __('Tidak ada siswa di kelas ini.') }}</div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Panel Kanan: Konfirmasi Kelulusan --}}
            <x-ui.card shadow>
                <div class="space-y-5">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-400">{{ __('Konfirmasi Kelulusan') }}</div>

                    <div
                        x-show="selectedGrad.length > 0"
                        class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 space-y-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="size-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                <x-ui.icon name="o-academic-cap" class="size-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <div class="font-black text-emerald-800 dark:text-emerald-300">
                                    <span x-text="selectedGrad.length"></span> {{ __('Siswa Siap Diwisuda') }}
                                </div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-500">{{ __('Status: Lulus | Kelas: dikosongkan') }}</div>
                            </div>
                        </div>

                        <div class="text-sm text-emerald-700 dark:text-emerald-400 leading-relaxed">
                            {{ __('Siswa yang dipilih akan:') }}
                        </div>
                        <ul class="text-sm text-emerald-700 dark:text-emerald-400 space-y-1 list-disc list-inside">
                            <li>{{ __('Statusnya diubah menjadi "Lulus"') }}</li>
                            <li>{{ __('Penempatan kelasnya dikosongkan') }}</li>
                        </ul>

                        <x-ui.button
                            x-on:click="graduate()"
                            wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-2xl transition-colors"
                            spinner="graduateStudents"
                        >
                            <x-ui.icon name="o-academic-cap" class="size-4" />
                            {{ __('Luluskan Siswa Terpilih') }}
                        </x-ui.button>
                    </div>

                    <div x-show="selectedGrad.length === 0" class="py-16 text-center opacity-30 italic text-sm">
                        {{ __('Pilih siswa di kiri untuk dikonfirmasi kelulusannya.') }}
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif
</div>
