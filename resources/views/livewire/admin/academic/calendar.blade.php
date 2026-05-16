<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\Level;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int $academicYearId = null;
    public ?int $filterLevelId = null;
    public string $filterType = '';
    public string $viewMode = 'calendar';
    public int $currentMonth;
    public int $currentYear;

    // Form fields
    public bool $eventModal = false;
    public bool $detailModal = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public string $type = 'rapat_jenjang';
    public string $scope = 'level';
    public ?int $levelId = null;
    public string $startDate = '';
    public ?string $endDate = null;
    public ?string $startTime = null;
    public ?string $endTime = null;
    public string $location = '';
    public ?string $color = null;
    public bool $isAllDay = false;
    public string $recurrenceType = 'none';
    public ?string $recurrenceEndDate = null;

    // Detail view
    public ?CalendarEvent $viewingEvent = null;

    public function mount(): void
    {
        $this->currentMonth = (int) now()->format('m');
        $this->currentYear = (int) now()->format('Y');

        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academicYearId = $activeYear?->id;
    }

    public function updatedType(): void
    {
        $autoScope = CalendarEvent::AUTO_SCOPE_TYPES[$this->type] ?? null;
        if ($autoScope) {
            $this->scope = $autoScope;
            if ($autoScope !== 'level') {
                $this->levelId = null;
            }
        }
    }

    public function previousMonth(): void
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
    }

    public function nextMonth(): void
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
    }

    public function goToToday(): void
    {
        $this->currentMonth = (int) now()->format('m');
        $this->currentYear = (int) now()->format('Y');
    }

    public function createNew(?string $date = null): void
    {
        $this->reset(['editingId', 'title', 'description', 'type', 'scope', 'levelId', 'startDate', 'endDate', 'startTime', 'endTime', 'location', 'color', 'isAllDay', 'recurrenceType', 'recurrenceEndDate']);
        $this->type = 'rapat_jenjang';
        $this->scope = 'level';
        $this->startDate = $date ?? now()->format('Y-m-d');
        $this->eventModal = true;
    }

    public function edit(int $id): void
    {
        $event = CalendarEvent::findOrFail($id);
        $this->editingId = $event->id;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->type = $event->type;
        $this->scope = $event->scope;
        $this->levelId = $event->level_id;
        $this->startDate = $event->start_date->format('Y-m-d');
        $this->endDate = $event->end_date?->format('Y-m-d');
        $this->startTime = $event->start_time;
        $this->endTime = $event->end_time;
        $this->location = $event->location ?? '';
        $this->color = $event->color;
        $this->isAllDay = $event->is_all_day;
        $this->recurrenceType = $event->recurrence_type ?? 'none';
        $this->recurrenceEndDate = $event->recurrence_end_date?->format('Y-m-d');
        $this->detailModal = false;
        $this->eventModal = true;
    }

    public function viewEvent(int $id): void
    {
        $this->viewingEvent = CalendarEvent::with(['level', 'academicYear', 'creator'])->findOrFail($id);
        $this->detailModal = true;
    }

    public function save(): void
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', array_keys(CalendarEvent::TYPE_LABELS))],
            'scope' => ['required', 'in:level,pkbm,yayasan'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'startTime' => ['nullable', 'date_format:H:i'],
            'endTime' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceEndDate' => ['nullable', 'date', 'after:startDate'],
        ];

        if ($this->scope === 'level') {
            $rules['levelId'] = ['required', 'exists:levels,id'];
        }

        $this->validate($rules);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'scope' => $this->scope,
            'level_id' => $this->scope === 'level' ? $this->levelId : null,
            'academic_year_id' => $this->academicYearId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate ?: null,
            'start_time' => $this->isAllDay ? null : $this->startTime,
            'end_time' => $this->isAllDay ? null : $this->endTime,
            'location' => $this->location ?: null,
            'color' => $this->color,
            'is_all_day' => $this->isAllDay,
            'recurrence_type' => $this->recurrenceType,
            'recurrence_end_date' => $this->recurrenceType !== 'none' ? $this->recurrenceEndDate : null,
            'created_by' => auth()->id(),
        ];

        if ($this->editingId) {
            CalendarEvent::findOrFail($this->editingId)->update($data);
        } else {
            CalendarEvent::create($data);
        }

        $this->eventModal = false;
        $this->reset(['editingId', 'title', 'description', 'type', 'scope', 'levelId', 'startDate', 'endDate', 'startTime', 'endTime', 'location', 'color', 'isAllDay', 'recurrenceType', 'recurrenceEndDate']);
    }

    public function delete(int $id): void
    {
        CalendarEvent::findOrFail($id)->delete();
        $this->detailModal = false;
        $this->viewingEvent = null;
    }

    public function getCalendarDaysProperty(): array
    {
        $firstDay = \Carbon\Carbon::create($this->currentYear, $this->currentMonth, 1);
        $lastDay = $firstDay->copy()->endOfMonth();
        $startOfCalendar = $firstDay->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endOfCalendar = $lastDay->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $days = [];
        $current = $startOfCalendar->copy();
        while ($current <= $endOfCalendar) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return $days;
    }

    public function with(): array
    {
        $firstDay = \Carbon\Carbon::create($this->currentYear, $this->currentMonth, 1);
        $startOfCalendar = $firstDay->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endOfCalendar = $firstDay->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $query = CalendarEvent::query()
            ->with(['level', 'creator'])
            ->parentsOnly()
            ->inDateRange($startOfCalendar->format('Y-m-d'), $endOfCalendar->format('Y-m-d'));

        if ($this->academicYearId) {
            $query->forAcademicYear($this->academicYearId);
        }

        if ($this->filterLevelId) {
            $query->forLevel($this->filterLevelId);
        }

        if ($this->filterType) {
            $query->byType($this->filterType);
        }

        $events = $query->orderBy('start_date')->orderBy('start_time')->get();

        $eventsByDate = [];
        foreach ($events as $event) {
            $start = $event->start_date;
            $end = $event->end_date ?? $event->start_date;
            
            // Find a slot that is free for the entire visible duration of the event
            $slot = 0;
            $visibleStart = $start->max($startOfCalendar);
            $visibleEnd = $end->min($endOfCalendar);

            while (true) {
                $isFree = true;
                $check = $visibleStart->copy();
                while ($check <= $visibleEnd) {
                    $key = $check->format('Y-m-d');
                    if (isset($eventsByDate[$key][$slot]) && $eventsByDate[$key][$slot] !== null) {
                        $isFree = false;
                        break;
                    }
                    $check->addDay();
                }
                if ($isFree) {
                    break;
                }
                $slot++;
            }
            
            // Assign the event to the found slot for its visible duration
            $current = $visibleStart->copy();
            while ($current <= $visibleEnd) {
                $key = $current->format('Y-m-d');
                if (!isset($eventsByDate[$key])) {
                    $eventsByDate[$key] = [];
                }
                // Pad with nulls if the slot is higher than current array length
                while (count($eventsByDate[$key]) <= $slot) {
                    $eventsByDate[$key][] = null;
                }
                $eventsByDate[$key][$slot] = $event;
                $current->addDay();
            }
        }

        $listQuery = CalendarEvent::query()
            ->with(['level', 'creator'])
            ->parentsOnly();

        if ($this->academicYearId) {
            $listQuery->forAcademicYear($this->academicYearId);
        }
        if ($this->filterLevelId) {
            $listQuery->forLevel($this->filterLevelId);
        }
        if ($this->filterType) {
            $listQuery->byType($this->filterType);
        }

        return [
            'eventsByDate' => $eventsByDate,
            'listEvents' => $listQuery->orderBy('start_date')->get(),
            'levels' => Level::all(),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get(),
            'typeOptions' => collect(CalendarEvent::TYPE_LABELS)->map(fn ($label, $key) => ['id' => $key, 'name' => $label])->values()->toArray(),
            'monthLabel' => \Carbon\Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y'),
        ];
    }
}; ?>

<div class="p-6 space-y-6">
    <x-ui.header :title="__('Kalender Pendidikan')" :subtitle="__('Jadwal rapat, ujian, dan kegiatan akademik.')">
        <x-slot:actions>
            <x-ui.button :label="__('Tambah Event')" icon="o-plus" class="btn-primary" wire:click="createNew" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-ui.select
            wire:model.live="academicYearId"
            :label="__('Tahun Ajaran')"
            :options="$academicYears"
            :placeholder="__('Semua')"
        />
        <x-ui.select
            wire:model.live="filterLevelId"
            :label="__('Jenjang')"
            :options="$levels"
            :placeholder="__('Semua Jenjang')"
        />
        <x-ui.select
            wire:model.live="filterType"
            :label="__('Tipe Event')"
            :options="$typeOptions"
            :placeholder="__('Semua Tipe')"
        />
        <div class="flex items-end gap-2">
            <x-ui.button
                :label="__('Kalender')"
                icon="o-calendar-days"
                wire:click="$set('viewMode', 'calendar')"
                @class(['btn-primary' => $viewMode === 'calendar'])
                ghost
            />
            <x-ui.button
                :label="__('Daftar')"
                icon="o-list-bullet"
                wire:click="$set('viewMode', 'list')"
                @class(['btn-primary' => $viewMode === 'list'])
                ghost
            />
        </div>
    </div>

    {{-- Calendar View --}}
    @if($viewMode === 'calendar')
    <x-ui.card shadow padding="false">
        <div class="p-4">
            {{-- Month Navigation --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <x-ui.button icon="o-chevron-left" wire:click="previousMonth" ghost sm />
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white min-w-[160px] text-center">
                        {{ $monthLabel }}
                    </h2>
                    <x-ui.button icon="o-chevron-right" wire:click="nextMonth" ghost sm />
                </div>
                <x-ui.button :label="__('Hari Ini')" wire:click="goToToday" ghost sm />
            </div>

            {{-- Calendar Grid --}}
            <div class="grid grid-cols-7 gap-px bg-slate-200 dark:bg-slate-700 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                {{-- Day Headers --}}
                @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
                    <div class="bg-slate-50 dark:bg-slate-800 px-2 py-2.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        {{ $dayName }}
                    </div>
                @endforeach

                {{-- Day Cells --}}
                @foreach($this->calendarDays as $day)
                    @php
                        $dateKey = $day->format('Y-m-d');
                        $isCurrentMonth = $day->month === $currentMonth;
                        $isToday = $day->isToday();
                        $dayEvents = $eventsByDate[$dateKey] ?? [];
                    @endphp
                    <div
                        wire:click="createNew('{{ $dateKey }}')"
                        @class([
                            'min-h-[90px] md:min-h-[110px] p-1.5 cursor-pointer transition-colors group',
                            'bg-white dark:bg-slate-900 hover:bg-blue-50/50 dark:hover:bg-slate-800/80' => $isCurrentMonth,
                            'bg-slate-50/70 dark:bg-slate-900/50' => !$isCurrentMonth,
                        ])
                    >
                        <div class="flex items-center justify-between h-7 mb-1.5">
                            <span @class([
                                'text-xs font-medium leading-none',
                                'w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white font-bold' => $isToday,
                                'text-slate-900 dark:text-white' => $isCurrentMonth && !$isToday,
                                'text-slate-400 dark:text-slate-600' => !$isCurrentMonth,
                            ])>
                                {{ $day->format('j') }}
                            </span>
                            @php
                                $validEventsCount = count(array_filter($dayEvents));
                            @endphp
                            @if($validEventsCount > 3)
                                <span class="text-[10px] font-medium text-slate-400 self-center">+{{ $validEventsCount - 3 }}</span>
                            @endif
                        </div>
                        <div class="space-y-0.5" wire:click.stop>
                            @foreach(array_slice($dayEvents, 0, 3) as $evt)
                                @if($evt)
                                    @php
                                        $isMultiDay = $evt->end_date && !$evt->start_date->eq($evt->end_date);
                                        $isStart = $isMultiDay && $day->isSameDay($evt->start_date);
                                        $isEnd = $isMultiDay && $day->isSameDay($evt->end_date);
                                        $isMiddle = $isMultiDay && !$isStart && !$isEnd;

                                        $roundedClass = 'rounded';
                                        $marginClass = '';
                                        $borderStyle = "border-left: 2px solid {$evt->display_color};";
                                        $titleText = $evt->title;

                                        if ($isMultiDay) {
                                            if ($isStart) {
                                                $roundedClass = 'rounded-l rounded-r-none';
                                                $marginClass = '-mr-1.5';
                                            } elseif ($isEnd) {
                                                $roundedClass = 'rounded-r rounded-l-none';
                                                $marginClass = '-ml-1.5';
                                                $borderStyle = '';
                                                $titleText = '';
                                            } else {
                                                $roundedClass = 'rounded-none';
                                                $marginClass = '-mx-1.5';
                                                $borderStyle = '';
                                                $titleText = '';
                                            }

                                            if (($isMiddle || $isEnd) && $day->dayOfWeek === \Carbon\Carbon::MONDAY) {
                                                $marginClass = $isEnd ? '' : '-mr-1.5';
                                                $roundedClass = $isEnd ? 'rounded' : 'rounded-l rounded-r-none';
                                                $borderStyle = "border-left: 2px solid {$evt->display_color};";
                                                $titleText = $evt->title;
                                            }
                                        }
                                    @endphp
                                    <div
                                        wire:click="viewEvent({{ $evt->id }})"
                                        class="text-[10px] md:text-[11px] leading-tight px-1.5 py-0.5 {{ $roundedClass }} {{ $marginClass }} truncate cursor-pointer font-medium transition-opacity hover:opacity-80 h-[1.375rem]"
                                        style="background-color: {{ $evt->display_color }}20; color: {{ $evt->display_color }}; {{ $borderStyle }}"
                                        title="{{ $evt->title }}"
                                    >
                                        {!! $titleText ? e($titleText) : '&nbsp;' !!}
                                    </div>
                                @else
                                    <div class="text-[10px] md:text-[11px] leading-tight px-1.5 py-0.5 opacity-0 pointer-events-none h-[1.375rem]">
                                        &nbsp;
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap gap-3 mt-4 pt-3 border-t border-slate-200 dark:border-slate-700">
                @foreach(\App\Models\CalendarEvent::TYPE_COLORS as $typeKey => $typeColor)
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $typeColor }};"></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ \App\Models\CalendarEvent::TYPE_LABELS[$typeKey] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.card>
    @endif

    {{-- List View --}}
    @if($viewMode === 'list')
    <x-ui.card shadow padding="false">
        <x-ui.table
            :headers="[
                ['key' => 'date', 'label' => __('Tanggal')],
                ['key' => 'title', 'label' => __('Judul')],
                ['key' => 'type', 'label' => __('Tipe')],
                ['key' => 'scope_info', 'label' => __('Cakupan')],
                ['key' => 'location', 'label' => __('Lokasi')],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ]"
            :rows="$listEvents"
        >
            @scope('cell_date', $event)
                <span class="text-sm font-medium text-slate-900 dark:text-white">
                    {{ $event->start_date->format('d M Y') }}
                </span>
                @if($event->end_date && !$event->start_date->eq($event->end_date))
                    <span class="text-xs text-slate-400"> - {{ $event->end_date->format('d M Y') }}</span>
                @endif
                @if(!$event->is_all_day && $event->start_time)
                    <div class="text-xs text-slate-400">{{ $event->start_time }} @if($event->end_time)- {{ $event->end_time }}@endif</div>
                @endif
            @endscope

            @scope('cell_title', $event)
                <span class="font-semibold text-slate-900 dark:text-white">{{ $event->title }}</span>
            @endscope

            @scope('cell_type', $event)
                <x-ui.badge :label="$event->type_label" flat size="xs" />
            @endscope

            @scope('cell_scope_info', $event)
                @if($event->scope === 'level' && $event->level)
                    <x-ui.badge :label="$event->level->name" flat size="xs" variant="info" />
                @else
                    <x-ui.badge :label="$event->scope_label" flat size="xs" variant="amber" />
                @endif
            @endscope

            @scope('cell_location', $event)
                <span class="text-sm text-slate-500">{{ $event->location ?? '-' }}</span>
            @endscope

            @scope('cell_actions', $event)
                <div class="flex justify-end gap-1">
                    <x-ui.button icon="o-eye" wire:click="viewEvent({{ $event->id }})" ghost sm />
                    <x-ui.button icon="o-pencil-square" wire:click="edit({{ $event->id }})" ghost sm />
                    <x-ui.button
                        icon="o-trash"
                        class="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                        wire:confirm="{{ __('Yakin ingin menghapus event ini?') }}"
                        wire:click="delete({{ $event->id }})"
                        ghost sm
                    />
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>
    @endif

    {{-- Event Form Modal --}}
    <x-ui.modal wire:model="eventModal" persistent maxWidth="max-w-xl">
        <x-ui.header
            :title="$editingId ? __('Edit Event') : __('Tambah Event Baru')"
            :subtitle="__('Isi detail event kalender.')"
            separator
        />

        <form wire:submit="save" class="space-y-4">
            <x-ui.input wire:model="title" :label="__('Judul Event')" required />

            <div class="grid grid-cols-2 gap-4">
                <x-ui.select
                    wire:model.live="type"
                    :label="__('Tipe Event')"
                    :options="$typeOptions"
                />

                @if(!isset(\App\Models\CalendarEvent::AUTO_SCOPE_TYPES[$type]))
                    <x-ui.select
                        wire:model.live="scope"
                        :label="__('Cakupan')"
                        :options="[
                            ['id' => 'level', 'name' => 'Jenjang'],
                            ['id' => 'pkbm', 'name' => 'PKBM (Semua)'],
                            ['id' => 'yayasan', 'name' => 'Yayasan'],
                        ]"
                    />
                @else
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">{{ __('Cakupan') }}</label>
                        <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm text-slate-600 dark:text-slate-400">
                            {{ \App\Models\CalendarEvent::SCOPE_LABELS[$scope] ?? $scope }}
                            <span class="text-xs text-slate-400">(otomatis)</span>
                        </div>
                    </div>
                @endif
            </div>

            @if($scope === 'level')
                <x-ui.select
                    wire:model="levelId"
                    :label="__('Jenjang')"
                    :options="$levels"
                    :placeholder="__('Pilih Jenjang')"
                    required
                />
            @endif

            <div class="grid grid-cols-2 gap-4">
                <x-ui.input wire:model="startDate" type="date" :label="__('Tanggal Mulai')" required />
                <x-ui.input wire:model="endDate" type="date" :label="__('Tanggal Selesai')" />
            </div>

            <x-ui.checkbox wire:model.live="isAllDay" :label="__('Seharian')" />

            @if(!$isAllDay)
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input wire:model="startTime" type="time" :label="__('Jam Mulai')" />
                    <x-ui.input wire:model="endTime" type="time" :label="__('Jam Selesai')" />
                </div>
            @endif

            <x-ui.input wire:model="location" :label="__('Lokasi')" icon="o-map-pin" />

            <x-ui.textarea wire:model="description" :label="__('Deskripsi')" rows="3" />

            {{-- Recurrence --}}
            <x-ui.select
                wire:model.live="recurrenceType"
                :label="__('Pengulangan')"
                :options="[
                    ['id' => 'none', 'name' => 'Tidak Berulang'],
                    ['id' => 'daily', 'name' => 'Harian'],
                    ['id' => 'weekly', 'name' => 'Mingguan'],
                    ['id' => 'monthly', 'name' => 'Bulanan'],
                ]"
            />

            @if($recurrenceType !== 'none')
                <x-ui.input wire:model="recurrenceEndDate" type="date" :label="__('Berulang Sampai')" />
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <x-ui.button :label="__('Batal')" ghost @click="show = false" />
                <x-ui.button :label="__('Simpan')" type="submit" class="btn-primary" spinner="save" />
            </div>
        </form>
    </x-ui.modal>

    {{-- Event Detail Modal --}}
    <x-ui.modal wire:model="detailModal" maxWidth="max-w-md">
        @if($viewingEvent)
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-3 h-3 rounded-full mt-1.5 shrink-0" style="background-color: {{ $viewingEvent->display_color }};"></div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $viewingEvent->title }}</h3>
                        <x-ui.badge :label="$viewingEvent->type_label" flat size="xs" class="mt-1" />
                        @if($viewingEvent->scope === 'level' && $viewingEvent->level)
                            <x-ui.badge :label="$viewingEvent->level->name" flat size="xs" variant="info" class="mt-1" />
                        @else
                            <x-ui.badge :label="$viewingEvent->scope_label" flat size="xs" variant="amber" class="mt-1" />
                        @endif
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                        <x-ui.icon name="o-calendar" class="w-4 h-4" />
                        <span>{{ $viewingEvent->start_date->translatedFormat('l, d F Y') }}</span>
                        @if($viewingEvent->end_date && !$viewingEvent->start_date->eq($viewingEvent->end_date))
                            <span>- {{ $viewingEvent->end_date->translatedFormat('d F Y') }}</span>
                        @endif
                    </div>

                    @if(!$viewingEvent->is_all_day && $viewingEvent->start_time)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <x-ui.icon name="o-clock" class="w-4 h-4" />
                            <span>{{ $viewingEvent->start_time }} @if($viewingEvent->end_time)- {{ $viewingEvent->end_time }}@endif</span>
                        </div>
                    @endif

                    @if($viewingEvent->location)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <x-ui.icon name="o-map-pin" class="w-4 h-4" />
                            <span>{{ $viewingEvent->location }}</span>
                        </div>
                    @endif

                    @if($viewingEvent->creator)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <x-ui.icon name="o-user" class="w-4 h-4" />
                            <span>{{ $viewingEvent->creator->name }}</span>
                        </div>
                    @endif
                </div>

                @if($viewingEvent->description)
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $viewingEvent->description }}</p>
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <x-ui.button icon="o-pencil-square" :label="__('Edit')" wire:click="edit({{ $viewingEvent->id }})" ghost sm />
                    <x-ui.button
                        icon="o-trash" :label="__('Hapus')"
                        class="text-red-600 dark:text-red-400"
                        wire:confirm="{{ __('Yakin ingin menghapus event ini?') }}"
                        wire:click="delete({{ $viewingEvent->id }})"
                        ghost sm
                    />
                </div>
            </div>
        @endif
    </x-ui.modal>
</div>
