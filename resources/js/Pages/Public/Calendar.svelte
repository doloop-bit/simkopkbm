<script module>
    import PublicLayout from "../../Layouts/PublicLayout.svelte";
    export const layout = PublicLayout;
</script>

<script>
    import PageHeader from "../../Components/PageHeader.svelte";

    let { schoolProfile, events = [], levels = [], typeLabels = {}, typeColors = {} } = $props();

    // Svelte State variables
    let currentMonth = $state(new Date().getMonth()); // 0-11
    let currentYear = $state(new Date().getFullYear());
    let selectedLevelId = $state("all");
    let selectedEvent = $state(null); // for detail modal

    // Date formatting helper
    function formatDateString(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    function isSameDay(d1, d2) {
        return (
            d1.getFullYear() === d2.getFullYear() &&
            d1.getMonth() === d2.getMonth() &&
            d1.getDate() === d2.getDate()
        );
    }

    // Month Names Indonesian
    const monthNamesIndo = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const monthLabel = $derived(`${monthNamesIndo[currentMonth]} ${currentYear}`);

    // Day of week headers
    const dayNames = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];

    // Navigation handlers
    function previousMonth() {
        if (currentMonth === 0) {
            currentMonth = 11;
            currentYear--;
        } else {
            currentMonth--;
        }
    }

    function nextMonth() {
        if (currentMonth === 11) {
            currentMonth = 0;
            currentYear++;
        } else {
            currentMonth++;
        }
    }

    function goToToday() {
        const today = new Date();
        currentMonth = today.getMonth();
        currentYear = today.getFullYear();
    }

    // Reactive filter: Client-side event filtering
    const filteredEvents = $derived(
        events.filter((event) => {
            if (selectedLevelId === "all") return true;
            // Scopes 'pkbm' and 'yayasan' are global and visible to all levels
            if (event.scope !== "level") return true;
            return Number(event.level_id) === Number(selectedLevelId);
        })
    );

    // Reactive 42-day slotted grid generator
    const calendarDays = $derived.by(() => {
        const days = [];
        const firstDay = new Date(currentYear, currentMonth, 1);
        let dayOfWeek = firstDay.getDay(); // 0 is Sunday, 1 is Monday, etc.
        // We start on Monday: if dayOfWeek is Sunday (0), we need 6 offset days; otherwise dayOfWeek - 1
        let startOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

        // Generate 42 days (6 weeks of 7 days)
        let current = new Date(currentYear, currentMonth, 1 - startOffset);
        for (let i = 0; i < 42; i++) {
            days.push({
                date: new Date(current),
                dateString: formatDateString(current),
                isCurrentMonth: current.getMonth() === currentMonth,
                isToday: isSameDay(current, new Date()),
                slottedEvents: []
            });
            current.setDate(current.getDate() + 1);
        }

        // Sort events: start_date asc, then longer duration first (so it occupies lower slot numbers)
        const sortedEvents = [...filteredEvents].sort((a, b) => {
            const startDiff = new Date(a.start_date) - new Date(b.start_date);
            if (startDiff !== 0) return startDiff;

            const durationA = a.end_date ? new Date(a.end_date) - new Date(a.start_date) : 0;
            const durationB = b.end_date ? new Date(b.end_date) - new Date(b.start_date) : 0;
            return durationB - durationA;
        });

        // Initialize slots map for each date string
        const slotsMap = {};
        days.forEach((d) => {
            slotsMap[d.dateString] = [];
        });

        const calendarStart = new Date(days[0].date);
        const calendarEnd = new Date(days[days.length - 1].date);

        // Assign events to free vertical slots across their duration
        sortedEvents.forEach((event) => {
            const start = new Date(event.start_date);
            const end = event.end_date ? new Date(event.end_date) : start;

            // Only process if within the calendar grid boundaries
            const visibleStart = new Date(Math.max(start, calendarStart));
            const visibleEnd = new Date(Math.min(end, calendarEnd));

            if (visibleStart <= visibleEnd) {
                // Find first free vertical slot indices
                let slot = 0;
                while (true) {
                    let isFree = true;
                    let check = new Date(visibleStart);
                    while (check <= visibleEnd) {
                        const key = formatDateString(check);
                        if (slotsMap[key] && slotsMap[key][slot] !== undefined) {
                            isFree = false;
                            break;
                        }
                        check.setDate(check.getDate() + 1);
                    }
                    if (isFree) break;
                    slot++;
                }

                // Place event in the chosen slot across its days
                let curr = new Date(visibleStart);
                while (curr <= visibleEnd) {
                    const key = formatDateString(curr);
                    if (slotsMap[key]) {
                        while (slotsMap[key].length <= slot) {
                            slotsMap[key].push(null);
                        }
                        slotsMap[key][slot] = event;
                    }
                    curr.setDate(curr.getDate() + 1);
                }
            }
        });

        // Assign the computed slot arrays back to our calendar days
        days.forEach((d) => {
            d.slottedEvents = slotsMap[d.dateString] || [];
        });

        return days;
    });

    // Helper to calculate event styling (width, spans, absolute offset) for each cell
    function getEventDisplay(day, cellIndex, evt) {
        if (!evt) return { isGhost: true };

        const isMultiDay = evt.end_date && evt.start_date !== evt.end_date;
        if (!isMultiDay) {
            return {
                isGhost: false,
                span: 1,
                widthStyle: "width: 100%;",
                roundedClass: "rounded-md",
                borderStyle: `border-left: 3px solid ${evt.display_color || "#6B7280"};`,
            };
        }

        const start = new Date(evt.start_date);
        const end = new Date(evt.end_date);
        const cur = new Date(day.date);

        const isStart = isSameDay(cur, start);
        const isMonday = cellIndex % 7 === 0;

        // Render bar ONLY on start day, or if it is Monday (first day of grid row)
        if (isStart || isMonday) {
            const daysInWeekLeft = 7 - (cellIndex % 7);
            const diffTime = end - cur;
            const eventDaysLeft = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            const span = Math.min(daysInWeekLeft, eventDaysLeft);

            const hasEndInThisWeek = cur.getTime() + (span - 1) * 86400000 >= end.getTime();

            let roundedClass = "rounded-none";
            if (isStart) roundedClass += " rounded-l-md";
            if (hasEndInThisWeek) roundedClass += " rounded-r-md";

            // Grid column span width formula matching calendar.blade.php:
            const widthStyle = `width: calc(${span}00% + ${span - 1} * 1px);`;

            return {
                isGhost: false,
                span,
                widthStyle,
                roundedClass,
                borderStyle: `border-left: 3px solid ${evt.display_color || "#6B7280"};`,
            };
        } else {
            // Render a height-preserving transparent ghost element for intermediate span days
            return { isGhost: true };
        }
    }

    // Modal action
    function openEventDetail(event) {
        selectedEvent = event;
    }

    function closeEventDetail() {
        selectedEvent = null;
    }
</script>

<PageHeader
    title="Kalender Pendidikan"
    description="Temukan informasi tanggal penting, agenda rapat, kegiatan PKBM, serta jadwal ujian di seluruh jenjang pendidikan kami secara transparan."
    breadcrumb="Kalender Pendidikan"
/>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-3xl shadow-[0_20px_50px_rgba(8,112,184,0.06)] border border-sky-100/70 p-6 md:p-8 space-y-8 animate-in fade-in duration-700">
        <!-- Header Filters -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 border-b border-slate-100 pb-6">
            <div class="space-y-1.5">
                <h2 class="text-2xl font-bold font-heading text-slate-800">
                    Jadwal Kegiatan Akademik
                </h2>
                <p class="text-sm text-slate-500 font-medium">
                    Filter berdasarkan jenjang belajar di bawah ini untuk melihat jadwal khusus.
                </p>
            </div>

            <!-- Filter Dropdown -->
            <div class="flex items-center gap-3 shrink-0">
                <span class="text-sm font-semibold text-slate-600">Pilih Jenjang:</span>
                <select
                    bind:value={selectedLevelId}
                    class="bg-slate-50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 min-w-[200px]"
                >
                    <option value="all">Semua Jenjang</option>
                    {#each levels as lvl}
                        <option value={lvl.id}>{lvl.name}</option>
                    {/each}
                </select>
            </div>
        </div>

        <!-- Month Navigation -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-1.5">
                <button
                    onclick={previousMonth}
                    class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-slate-800 transition-all active:scale-95 cursor-pointer"
                    aria-label="Bulan Sebelumnya"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h3 class="text-xl md:text-2xl font-bold text-slate-800 min-w-[200px] text-center font-heading">
                    {monthLabel}
                </h3>
                <button
                    onclick={nextMonth}
                    class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-slate-800 transition-all active:scale-95 cursor-pointer"
                    aria-label="Bulan Berikutnya"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <button
                onclick={goToToday}
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all active:scale-95 shadow-sm cursor-pointer"
            >
                Bulan Ini
            </button>
        </div>

        <!-- Grid Calendar -->
        <div class="border border-slate-200/80 rounded-2xl overflow-hidden bg-slate-200 gap-[1px] grid grid-cols-7">
            <!-- Day of Week Headers -->
            {#each dayNames as dayName}
                <div class="bg-slate-50 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                    {dayName}
                </div>
            {/each}

            <!-- Day Cells -->
            {#each calendarDays as day, i}
                {@const isCurrentMonth = day.isCurrentMonth}
                {@const isToday = day.isToday}
                <div
                    class="min-h-[105px] md:min-h-[125px] p-2 bg-white flex flex-col relative transition-colors duration-150 {isCurrentMonth ? 'text-slate-800' : 'bg-slate-50/50 text-slate-400'}"
                >
                    <!-- Day Indicator Row -->
                    <div class="flex items-center justify-between mb-1.5 h-6">
                        <span
                            class="text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full {isToday ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : ''}"
                        >
                            {day.date.getDate()}
                        </span>
                        {#if day.slottedEvents.filter(Boolean).length > 3}
                            <span class="text-[9px] font-bold text-slate-400">
                                +{day.slottedEvents.filter(Boolean).length - 3} agenda
                            </span>
                        {/if}
                    </div>

                    <!-- Slotted Event bars -->
                    <div class="space-y-1 relative grow">
                        {#each day.slottedEvents.slice(0, 3) as evt}
                            {#if evt}
                                {@const display = getEventDisplay(day, i, evt)}
                                {#if !display.isGhost}
                                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                                    <div
                                        onclick={() => openEventDetail(evt)}
                                        class="text-[10px] md:text-[11px] leading-tight px-2 py-1 select-none cursor-pointer truncate font-semibold transition-all duration-150 h-[1.5rem] relative z-10 hover:!bg-[var(--evt-bg-hover)] shadow-xs {display.roundedClass}"
                                        style="background-color: {evt.display_color}18; --evt-bg-hover: {evt.display_color}35; color: {evt.display_color}; {display.borderStyle} {display.widthStyle}"
                                        title={evt.title}
                                    >
                                        {evt.title}
                                    </div>
                                {:else}
                                    <!-- Transparent ghost item for layout preserving -->
                                    <div class="text-[10px] md:text-[11px] px-2 py-1 opacity-0 pointer-events-none h-[1.5rem] select-none">
                                        &nbsp;
                                    </div>
                                {/if}
                            {:else}
                                <!-- Empty slot spacer -->
                                <div class="text-[10px] md:text-[11px] px-2 py-1 opacity-0 pointer-events-none h-[1.5rem] select-none">
                                    &nbsp;
                                </div>
                            {/if}
                        {/each}
                    </div>
                </div>
            {/each}
        </div>

        <!-- Legend Area -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-6 border-t border-slate-100">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider shrink-0">Kategori Warna:</span>
            <div class="flex flex-wrap gap-4">
                {#each Object.entries(typeColors) as [typeKey, color]}
                    {#if typeLabels[typeKey]}
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full shrink-0 shadow-xs" style="background-color: {color};"></span>
                            <span class="text-xs font-bold text-slate-600">{typeLabels[typeKey]}</span>
                        </div>
                    {/if}
                {/each}
            </div>
        </div>
    </div>
</section>

<!-- Beautiful Svelte Modal for Event Details -->
{#if selectedEvent}
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        onclick={closeEventDetail}
        class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
        <div
            onclick={(e) => e.stopPropagation()}
            class="bg-white rounded-3xl shadow-2xl border border-sky-100/50 w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200"
        >
            <!-- Modal Accent Header -->
            <div class="h-2" style="background-color: {selectedEvent.display_color};"></div>

            <div class="p-6 md:p-8 space-y-6">
                <!-- Header content -->
                <div class="flex justify-between items-start gap-4">
                    <div class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                style="background-color: {selectedEvent.display_color}15; color: {selectedEvent.display_color}"
                            >
                                {selectedEvent.type_label}
                            </span>
                            {#if selectedEvent.level}
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700">
                                    {selectedEvent.level.name}
                                </span>
                            {:else}
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700">
                                    {selectedEvent.scope_label}
                                </span>
                            {/if}
                        </div>
                        <h4 class="text-2xl font-bold font-heading text-slate-800 leading-tight">
                            {selectedEvent.title}
                        </h4>
                    </div>
                    <button
                        onclick={closeEventDetail}
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer"
                        aria-label="Tutup Detail"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-6">
                    <!-- Date Range Info -->
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-slate-50 text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Waktu Agenda</span>
                            <span class="text-sm font-bold text-slate-700">
                                {new Date(selectedEvent.start_date).toLocaleDateString("id-ID", {
                                    weekday: "long",
                                    year: "numeric",
                                    month: "long",
                                    day: "numeric",
                                })}
                                {#if selectedEvent.end_date && selectedEvent.start_date !== selectedEvent.end_date}
                                    - {new Date(selectedEvent.end_date).toLocaleDateString("id-ID", {
                                        year: "numeric",
                                        month: "long",
                                        day: "numeric",
                                    })}
                                {/if}
                            </span>
                            {#if !selectedEvent.is_all_day && selectedEvent.start_time}
                                <span class="text-xs font-semibold text-slate-500 block">
                                    Pukul {selectedEvent.start_time} {selectedEvent.end_time ? `- ${selectedEvent.end_time}` : ""} WIB
                                </span>
                            {:else}
                                <span class="text-xs font-semibold text-slate-500 block">Seharian Penuh</span>
                            {/if}
                        </div>
                    </div>

                    <!-- Location Info -->
                    {#if selectedEvent.location}
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-slate-50 text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Lokasi</span>
                                <span class="text-sm font-bold text-slate-700">{selectedEvent.location}</span>
                            </div>
                        </div>
                    {/if}
                </div>

                <!-- Description -->
                {#if selectedEvent.description}
                    <div class="border-t border-slate-100 pt-6">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Deskripsi Detail</span>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line font-medium">
                            {selectedEvent.description}
                        </p>
                    </div>
                {/if}

                <!-- Footer button -->
                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button
                        onclick={closeEventDetail}
                        class="px-6 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200/85 text-slate-700 text-sm font-bold transition-all active:scale-95 cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
{/if}
