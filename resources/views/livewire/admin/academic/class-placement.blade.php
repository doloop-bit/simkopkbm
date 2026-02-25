<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentProfile;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $academic_year_id = null;
    public ?int $level_id = null;
    public ?string $source_classroom_id = null;
    public ?string $target_classroom_id = null;
    
    public array $sourceStudents = [];
    public array $targetStudents = [];
    
    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academic_year_id = $activeYear->id;
        }
    }
    
    public function updatedLevelId(): void
    {
        $this->source_classroom_id = null;
        $this->target_classroom_id = null;
        $this->sourceStudents = [];
        $this->targetStudents = [];
    }
    
    public function updatedSourceClassroomId(): void
    {
        $this->loadSourceStudents();
    }
    
    public function updatedTargetClassroomId(): void
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
            \Flux::toast('Tidak ada siswa yang dipilih');
            return;
        }

        $targetId = $targetClassroomId === 'unassigned' ? null : (int) $targetClassroomId;

        DB::transaction(function () use ($studentIds, $targetId) {
            StudentProfile::whereIn('id', $studentIds)
                ->update(['classroom_id' => $targetId]);
        });

        $this->loadSourceStudents();
        $this->loadTargetStudents();
        
        // Notify
        $count = count($studentIds);
        \Flux::toast("$count siswa berhasil dipindahkan.");
    }
    
    public function moveStudentsFromAlpine(array $studentIds, $newTargetId)
    {
        if (!$newTargetId) {
            \Flux::toast('Pilih kelas tujuan terlebih dahulu', variant: 'danger');
            return;
        }
        $this->moveStudents($studentIds, $newTargetId);
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

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Penempatan Kelas</flux:heading>
            <flux:subheading>Pindahkan siswa antar kelas secara massal.</flux:subheading>
        </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
        <flux:select wire:model.live="academic_year_id" label="Tahun Ajaran">
            <option value="">Pilih Tahun</option>
            @foreach($years as $year)
                <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
            @endforeach
        </flux:select>
        
        <flux:select wire:model.live="level_id" label="Jenjang">
            <option value="">Pilih Jenjang</option>
            @foreach($levels as $level)
                <option value="{{ $level->id }}">{{ $level->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($academic_year_id && $level_id)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2" 
             x-data="{ 
                selectedSource: [], 
                selectedTarget: [],
                draggingSource: false,
                draggingTarget: false,
                toggleSource(id) {
                    if (this.selectedSource.includes(id)) {
                        this.selectedSource = this.selectedSource.filter(i => i !== id);
                    } else {
                        this.selectedSource.push(id);
                    }
                },
                toggleTarget(id) {
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
                        this.selectedSource = ids;
                    }
                },
                selectAllTarget(ids) {
                    if (this.selectedTarget.length === ids.length) {
                        this.selectedTarget = [];
                    } else {
                        this.selectedTarget = ids;
                    }
                },
                getDragDataSource(id) {
                    // if user drags an item not in selection, select it solely
                    if(!this.selectedSource.includes(id)) this.selectedSource = [id];
                    return JSON.stringify({ source: 'source', ids: this.selectedSource });
                },
                getDragDataTarget(id) {
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
            <div class="flex flex-col overflow-hidden border rounded-lg bg-zinc-50 border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <flux:select wire:model.live="source_classroom_id" label="Kelas Asal">
                        <option value="">Pilih Kelas Asal / Status Baru</option>
                        <option value="unassigned">- Belum Ada Kelas (Siswa Baru) -</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <div class="flex items-center justify-between mt-4">
                        <flux:checkbox 
                            x-on:click="selectAllSource({{ json_encode(array_column($sourceStudents, 'id')) }})" 
                            x-bind:checked="selectedSource.length > 0 && selectedSource.length === {{ count($sourceStudents) }}"
                            label="Pilih Semua ({{ count($sourceStudents) }})" />
                        
                        <flux:button size="sm" x-show="selectedSource.length > 0" x-on:click="moveToTarget" variant="primary" class="ml-2">
                            Pindahkan ke Kanan <flux:icon icon="arrow-right" class="w-4 h-4 ml-1" />
                        </flux:button>
                    </div>
                </div>
                
                <div class="p-2 overflow-y-auto h-[450px]"
                     x-on:dragover.prevent="draggingSource = true"
                     x-on:dragleave.prevent="draggingSource = false"
                     x-on:drop.prevent="onDropSource($event)"
                     x-bind:class="draggingSource ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-indigo-500 rounded' : ''">
                    <div class="space-y-1 pb-4">
                        @if(count($sourceStudents) === 0 && $source_classroom_id)
                            <div class="py-8 text-center flex justify-center text-zinc-500 dark:text-zinc-400">
                                Tidak ada siswa di kelas ini.
                            </div>
                        @endif
                        
                        @foreach($sourceStudents as $student)
                            <div class="p-3 border rounded-lg shadow-sm flex items-center bg-white border-zinc-200 dark:bg-zinc-900 dark:border-zinc-700 cursor-move hover:border-indigo-500 transition-colors"
                                 draggable="true"
                                 x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataSource({{ $student['id'] }}))"
                                 x-bind:class="selectedSource.includes({{ $student['id'] }}) ? 'ring-2 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : ''"
                                 x-on:click="toggleSource({{ $student['id'] }})">
                                 
                                <flux:icon icon="bars-3" class="w-4 h-4 mr-3 text-zinc-400" />
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $student['name'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                </div>
                                <!-- Stop propagation so the div click doesn't trigger twice -->
                                <div x-on:click.stop>
                                    <flux:checkbox id="source-{{ $student['id'] }}" x-model="selectedSource" value="{{ $student['id'] }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Panel Kanan (Tujuan) -->
            <div class="flex flex-col overflow-hidden border rounded-lg bg-zinc-50 border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <flux:select wire:model.live="target_classroom_id" label="Kelas Tujuan">
                        <option value="">Pilih Kelas Tujuan</option>
                        <option value="unassigned">- Belum Ada Kelas (Cabut Siswa) -</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <div class="flex items-center justify-between mt-4">
                        <flux:checkbox 
                            x-on:click="selectAllTarget({{ json_encode(array_column($targetStudents, 'id')) }})" 
                            x-bind:checked="selectedTarget.length > 0 && selectedTarget.length === {{ count($targetStudents) }}"
                            label="Pilih Semua ({{ count($targetStudents) }})" />
                        
                        <flux:button size="sm" x-show="selectedTarget.length > 0" x-on:click="moveToSource" variant="primary" class="mr-2">
                            <flux:icon icon="arrow-left" class="w-4 h-4 mr-1" /> Pindahkan ke Kiri 
                        </flux:button>
                    </div>
                </div>
                
                <div class="p-2 overflow-y-auto h-[450px]"
                     x-on:dragover.prevent="draggingTarget = true"
                     x-on:dragleave.prevent="draggingTarget = false"
                     x-on:drop.prevent="onDropTarget($event)"
                     x-bind:class="draggingTarget ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-indigo-500 rounded' : ''">
                    <div class="space-y-1 pb-4">
                        @if(count($targetStudents) === 0 && $target_classroom_id)
                            <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                Pilih / Drag siswa ke sini.
                            </div>
                        @endif
                        
                        @foreach($targetStudents as $student)
                            <div class="p-3 border rounded-lg shadow-sm flex items-center bg-white border-zinc-200 dark:bg-zinc-900 dark:border-zinc-700 cursor-move hover:border-indigo-500 transition-colors"
                                 draggable="true"
                                 x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataTarget({{ $student['id'] }}))"
                                 x-bind:class="selectedTarget.includes({{ $student['id'] }}) ? 'ring-2 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : ''"
                                 x-on:click="toggleTarget({{ $student['id'] }})">
                                 
                                <flux:icon icon="bars-3" class="w-4 h-4 mr-3 text-zinc-400" />
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $student['name'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">NIS: {{ $student['nis'] ?? '-' }}</div>
                                </div>
                                <div x-on:click.stop>
                                    <flux:checkbox id="target-{{ $student['id'] }}" x-model="selectedTarget" value="{{ $student['id'] }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
        </div>
    @else
        <div class="p-8 text-center border border-dashed rounded-lg border-zinc-300 dark:border-zinc-700 mt-6">
            <p class="text-zinc-500 dark:text-zinc-400">Pilih Tahun Ajaran dan Jenjang untuk mulai memindahkan kelas siswa.</p>
        </div>
    @endif
</div> 
