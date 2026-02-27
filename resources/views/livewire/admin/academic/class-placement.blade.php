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
        
        // Notify
        $count = count($studentIds);
        session()->flash('success', "$count siswa berhasil dipindahkan.");
    }
    
    public function moveStudentsFromAlpine(array $studentIds, $newTargetId)
    {
        if (!$newTargetId) {
            session()->flash('error', 'Pilih kelas tujuan terlebih dahulu');
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
    <x-header title="Penempatan Kelas" subtitle="Pindahkan siswa antar kelas secara massal." separator />

    @if (session('success'))
        <x-alert title="Berhasil" icon="o-check-circle" class="alert-success mb-6" dismissible>
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert title="Error" icon="o-exclamation-triangle" class="alert-error mb-6" dismissible>
            {{ session('error') }}
        </x-alert>
    @endif

    <!-- Filters -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
        <x-select wire:model.live="academic_year_id" label="Tahun Ajaran" :options="$years" placeholder="Pilih Tahun" />
        <x-select wire:model.live="level_id" label="Jenjang" :options="$levels" placeholder="Pilih Jenjang" />
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
                        this.selectedSource.push(id.toString());
                    }
                },
                toggleTarget(id) {
                    if (this.selectedTarget.includes(id)) {
                        this.selectedTarget = this.selectedTarget.filter(i => i !== id);
                    } else {
                        this.selectedTarget.push(id.toString());
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
                    if(!this.selectedSource.includes(id.toString())) this.selectedSource = [id.toString()];
                    return JSON.stringify({ source: 'source', ids: this.selectedSource });
                },
                getDragDataTarget(id) {
                    if(!this.selectedTarget.includes(id.toString())) this.selectedTarget = [id.toString()];
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
            <div class="flex flex-col overflow-hidden border rounded-xl bg-base-200 border-base-300 shadow-sm">
                <div class="p-4 border-b border-base-300 bg-base-100">
                    <x-select wire:model.live="source_classroom_id" label="Kelas Asal">
                        <option value="">Pilih Kelas Asal / Status Baru</option>
                        <option value="unassigned">- Belum Ada Kelas (Siswa Baru) -</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </x-select>
                    
                    <div class="flex items-center justify-between mt-4">
                        <x-checkbox 
                            x-on:click="selectAllSource({{ json_encode(array_column($sourceStudents, 'id')) }})" 
                            x-bind:checked="selectedSource.length > 0 && selectedSource.length === {{ count($sourceStudents) }}"
                            label="Pilih Semua ({{ count($sourceStudents) }})" />
                        
                        <x-button sm x-show="selectedSource.length > 0" x-on:click="moveToTarget" class="btn-primary">
                            Pindah Kanan <x-icon name="o-arrow-right" class="w-4 h-4 ml-1" />
                        </x-button>
                    </div>
                </div>
                
                <div class="p-2 overflow-y-auto h-[500px]"
                     x-on:dragover.prevent="draggingSource = true"
                     x-on:dragleave.prevent="draggingSource = false"
                     x-on:drop.prevent="onDropSource($event)"
                     x-bind:class="draggingSource ? 'bg-primary/5 ring-2 ring-primary rounded-xl' : ''">
                    <div class="space-y-2 pb-4">
                        @if(count($sourceStudents) === 0 && $source_classroom_id)
                            <div class="py-12 text-center opacity-40">
                                Tidak ada siswa di kelas ini.
                            </div>
                        @endif
                        
                        @foreach($sourceStudents as $student)
                            <div class="p-3 border rounded-xl shadow-sm flex items-center bg-base-100 border-base-300 cursor-move hover:border-primary transition-all group"
                                 draggable="true"
                                 x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataSource({{ $student['id'] }}))"
                                 x-bind:class="selectedSource.includes('{{ $student['id'] }}') ? 'ring-2 ring-primary border-primary bg-primary/5' : ''"
                                 x-on:click="toggleSource({{ $student['id'] }})">
                                 
                                <x-icon name="o-bars-3" class="w-4 h-4 mr-3 opacity-30 group-hover:opacity-100" />
                                <div class="flex-1">
                                    <div class="font-bold text-sm">{{ $student['name'] }}</div>
                                    <div class="text-xs opacity-60">NIS: {{ $student['nis'] ?? '-' }}</div>
                                </div>
                                <div x-on:click.stop>
                                    <x-checkbox id="source-{{ $student['id'] }}" x-model="selectedSource" value="{{ $student['id'] }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Panel Kanan (Tujuan) -->
            <div class="flex flex-col overflow-hidden border rounded-xl bg-base-200 border-base-300 shadow-sm">
                <div class="p-4 border-b border-base-300 bg-base-100">
                    <x-select wire:model.live="target_classroom_id" label="Kelas Tujuan">
                        <option value="">Pilih Kelas Tujuan</option>
                        <option value="unassigned">- Belum Ada Kelas (Cabut Siswa) -</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </x-select>
                    
                    <div class="flex items-center justify-between mt-4">
                        <x-checkbox 
                            x-on:click="selectAllTarget({{ json_encode(array_column($targetStudents, 'id')) }})" 
                            x-bind:checked="selectedTarget.length > 0 && selectedTarget.length === {{ count($targetStudents) }}"
                            label="Pilih Semua ({{ count($targetStudents) }})" />
                        
                        <x-button sm x-show="selectedTarget.length > 0" x-on:click="moveToSource" class="btn-primary">
                            <x-icon name="o-arrow-left" class="w-4 h-4 mr-1" /> Pindah Kiri 
                        </x-button>
                    </div>
                </div>
                
                <div class="p-2 overflow-y-auto h-[500px]"
                     x-on:dragover.prevent="draggingTarget = true"
                     x-on:dragleave.prevent="draggingTarget = false"
                     x-on:drop.prevent="onDropTarget($event)"
                     x-bind:class="draggingTarget ? 'bg-primary/5 ring-2 ring-primary rounded-xl' : ''">
                    <div class="space-y-2 pb-4">
                        @if(count($targetStudents) === 0 && $target_classroom_id)
                            <div class="py-12 text-center opacity-40">
                                Pilih / Drag siswa ke sini.
                            </div>
                        @endif
                        
                        @foreach($targetStudents as $student)
                            <div class="p-3 border rounded-xl shadow-sm flex items-center bg-base-100 border-base-300 cursor-move hover:border-primary transition-all group"
                                 draggable="true"
                                 x-on:dragstart="$event.dataTransfer.setData('text/plain', getDragDataTarget({{ $student['id'] }}))"
                                 x-bind:class="selectedTarget.includes('{{ $student['id'] }}') ? 'ring-2 ring-primary border-primary bg-primary/5' : ''"
                                 x-on:click="toggleTarget({{ $student['id'] }})">
                                 
                                <x-icon name="o-bars-3" class="w-4 h-4 mr-3 opacity-30 group-hover:opacity-100" />
                                <div class="flex-1">
                                    <div class="font-bold text-sm">{{ $student['name'] }}</div>
                                    <div class="text-xs opacity-60">NIS: {{ $student['nis'] ?? '-' }}</div>
                                </div>
                                <div x-on:click.stop>
                                    <x-checkbox id="target-{{ $student['id'] }}" x-model="selectedTarget" value="{{ $student['id'] }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
        </div>
    @else
        <div class="py-20 text-center border-2 border-dashed rounded-3xl border-base-300 bg-base-200/50 mt-6 box-border">
            <x-icon name="o-academic-cap" class="w-16 h-16 mb-4 opacity-20" />
            <p class="text-xl font-medium opacity-50">Pilih Tahun Ajaran dan Jenjang untuk mulai memindahkan kelas siswa.</p>
        </div>
    @endif
</div> 
