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

<div class="p-6 space-y-6 text-slate-900 dark:text-white">
    <x-ui.header :title="__('Penempatan Kelas')" :subtitle="__('Pindahkan siswa antar kelas secara massal.')" separator />

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
                <x-ui.icon name="o-academic-cap" class="size-12 text-primary opacity-20" />
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">{{ __('Siap Pindah Kelas?') }}</h3>
            <p class="text-slate-400 dark:text-slate-500 text-base max-w-md text-center leading-relaxed">
                {{ __('Pilih Tahun Ajaran dan Jenjang terlebih dahulu untuk mulai memindahkan atau menempatkan siswa pada rombongan belajar yang tepat.') }}
            </p>
        </div>
    @endif
</div> 
