<script>
    import { onMount } from 'svelte';

    let { 
        label, 
        name, 
        value = $bindable(), 
        error = '', 
        required = false,
        disabled = false,
        placeholder = 'Pilih Tanggal...',
        class: className = ''
    } = $props();

    let isOpen = $state(false);
    let container;
    
    // Internal state for calendar navigation
    let viewDate = $state(new Date());
    if (value) {
        let valDate = new Date(value);
        if (!isNaN(valDate)) viewDate = valDate;
    }

    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    // Derived helpers
    let currentMonth = $derived(viewDate.getMonth());
    let currentYear = $derived(viewDate.getFullYear());

    function getDaysInMonth(month, year) {
        return new Date(year, month + 1, 0).getDate();
    }

    function getFirstDayOfMonth(month, year) {
        return new Date(year, month, 1).getDay();
    }

    let calendarDays = $derived.by(() => {
        const firstDay = getFirstDayOfMonth(currentMonth, currentYear);
        const daysInMonth = getDaysInMonth(currentMonth, currentYear);
        const prevMonthDays = getDaysInMonth(currentMonth - 1, currentYear);
        
        const result = [];
        
        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            result.push({ day: prevMonthDays - i, month: currentMonth - 1, year: currentYear, current: false });
        }
        
        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            result.push({ day: i, month: currentMonth, year: currentYear, current: true });
        }
        
        // Next month days
        const totalFetched = result.length;
        for (let i = 1; i <= 42 - totalFetched; i++) {
            result.push({ day: i, month: currentMonth + 1, year: currentYear, current: false });
        }
        
        return result;
    });

    function selectDate(dateObj) {
        const date = new Date(dateObj.year, dateObj.month, dateObj.day);
        value = date.toISOString().split('T')[0];
        isOpen = false;
    }

    function changeMonth(delta) {
        viewDate = new Date(currentYear, currentMonth + delta, 1);
    }

    function changeYear(delta) {
        viewDate = new Date(currentYear + delta, currentMonth, 1);
    }

    function handleClickOutside(event) {
        if (container && !container.contains(event.target)) {
            isOpen = false;
        }
    }

    function formatDisplayDate(val) {
        if (!val) return '';
        const d = new Date(val);
        if (isNaN(d)) return val;
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    onMount(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    });

    // Year selection range (for birthdates)
    let yearRange = $derived.by(() => {
        const years = [];
        const startYear = new Date().getFullYear() - 25;
        const endYear = new Date().getFullYear() + 5;
        for (let i = endYear; i >= startYear; i--) years.push(i);
        return years;
    });
</script>

<div class="space-y-2 {className}" bind:this={container}>
    {#if label}
        <label for={name} class="text-xs font-semibold text-slate-500 tracking-wide ml-1">
            {label}
            {#if required}
                <span class="text-rose-500">*</span>
            {/if}
        </label>
    {/if}
    
    <div class="relative group">
        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-500 transition-colors pointer-events-none">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>

        <button
            type="button"
            {name}
            id={name}
            onclick={() => isOpen = !isOpen}
            {disabled}
            class="w-full text-left bg-slate-50 dark:bg-slate-800 border-none ring-1 {error ? 'ring-rose-500' : 'ring-slate-100 dark:ring-slate-700'} rounded-xl pl-12 pr-5 py-2.5 font-medium tracking-tight text-sm focus:ring-2 focus:ring-amber-500/50 transition-all outline-none {value ? 'text-slate-800 dark:text-white' : 'text-slate-400'}"
        >
            {formatDisplayDate(value) || placeholder}
        </button>

        {#if isOpen && !disabled}
            <div class="absolute z-50 mt-2 p-4 bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200 origin-top w-[320px]">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <button type="button" onclick={() => changeMonth(-1)} aria-label="Bulan Sebelumnya" class="p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    
                    <div class="flex items-center gap-1">
                        <span class="text-sm font-bold text-slate-800 dark:text-white">{months[currentMonth]}</span>
                        <select 
                            value={currentYear} 
                            onchange={(e) => viewDate = new Date(parseInt(e.target.value), currentMonth, 1)}
                            class="bg-transparent border-none text-sm font-bold text-amber-600 outline-none cursor-pointer p-0"
                        >
                            {#each Array.from({length: 100}, (_, i) => new Date().getFullYear() - 80 + i) as year}
                                <option value={year}>{year}</option>
                            {/each}
                        </select>
                    </div>

                    <button type="button" onclick={() => changeMonth(1)} aria-label="Bulan Berikutnya" class="p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Day Names -->
                <div class="grid grid-cols-7 mb-2">
                    {#each days as day}
                        <span class="text-[10px] font-bold text-slate-400 text-center uppercase tracking-widest">{day}</span>
                    {/each}
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-7 gap-1">
                    {#each calendarDays as d}
                        {@const isToday = new Date().toDateString() === new Date(d.year, d.month, d.day).toDateString()}
                        {@const isSelected = value === new Date(d.year, d.month, d.day).toISOString().split('T')[0]}
                        <button
                            type="button"
                            onclick={() => selectDate(d)}
                            class="aspect-square flex items-center justify-center text-xs rounded-lg transition-all
                            {d.current ? 'font-semibold' : 'text-slate-300 dark:text-slate-700'}
                            {isSelected ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'hover:bg-amber-50 dark:hover:bg-amber-900/20 text-slate-700 dark:text-slate-300'}
                            {isToday && !isSelected ? 'ring-1 ring-amber-500/30 text-amber-500' : ''}"
                        >
                            {d.day}
                        </button>
                    {/each}
                </div>

                <!-- Footer -->
                <div class="mt-4 pt-4 border-t border-slate-50 dark:border-slate-800 flex justify-between">
                    <button type="button" onclick={() => { value = new Date().toISOString().split('T')[0]; isOpen = false; }} class="text-[10px] font-bold text-amber-500 uppercase tracking-widest hover:text-amber-600">Hari Ini</button>
                    <button type="button" onclick={() => { value = ''; isOpen = false; }} class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hover:text-slate-600">Hapus</button>
                </div>
            </div>
        {/if}
    </div>
    
    {#if error}
        <p class="text-[10px] font-semibold text-rose-500 ml-1 animate-in fade-in slide-in-from-top-1">
            {error}
        </p>
    {/if}
</div>
