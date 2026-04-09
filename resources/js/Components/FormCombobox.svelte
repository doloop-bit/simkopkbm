<script>
    import { onMount, tick } from 'svelte';

    let { 
        label, 
        name, 
        value = $bindable(), 
        options = [], 
        error = '', 
        placeholder = 'Cari atau pilih...', 
        required = false,
        disabled = false,
        class: className = ''
    } = $props();

    let isOpen = $state(false);
    let searchTerm = $state('');
    let highlightedIndex = $state(-1);
    let container;
    let inputRef;

    // Filtered options based on search term
    let filteredOptions = $derived(
        searchTerm 
            ? options.filter(opt => 
                (opt.name || '').toLowerCase().includes(searchTerm.toLowerCase())
            )
            : options.slice(0, 100) // Show first 100 by default for performance
    );

    // Watch for outside clicks to close the dropdown
    function handleClickOutside(event) {
        if (container && !container.contains(event.target)) {
            isOpen = false;
        }
    }

    onMount(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    });

    // When value changes from outside, update search term display
    $effect(() => {
        if (value) {
            const selected = options.find(opt => (opt.code || opt.id) === value);
            if (selected) {
                const rawName = selected.name || selected.label || '';
                searchTerm = rawName.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            } else {
                // If value exists but not in list, it's a custom entry (Luar Negeri)
                searchTerm = value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
            }
        } else {
            searchTerm = '';
        }
    });

    function selectOption(option) {
        if (typeof option === 'string') {
            value = option;
            searchTerm = option.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
        } else {
            value = option.code || option.id;
            const rawName = option.name || option.label || '';
            searchTerm = rawName.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
        }
        isOpen = false;
        highlightedIndex = -1;
    }

    function handleInput(e) {
        searchTerm = e.target.value;
        value = searchTerm; // Update value immediately to allow "Luar Negeri" custom typing
        isOpen = true;
        highlightedIndex = 0;
    }

    function handleKeyDown(e) {
        if (!isOpen && (e.key === 'ArrowDown' || e.key === 'Enter')) {
            isOpen = true;
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex = Math.min(highlightedIndex + 1, filteredOptions.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex = Math.max(highlightedIndex - 1, 0);
        } else if (e.key === 'Enter' && isOpen) {
            e.preventDefault();
            if (highlightedIndex >= 0 && filteredOptions[highlightedIndex]) {
                selectOption(filteredOptions[highlightedIndex]);
            } else {
                isOpen = false;
            }
        } else if (e.key === 'Escape') {
            isOpen = false;
        }
    }
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <input
            type="text"
            {name}
            id={name}
            bind:this={inputRef}
            autocomplete="off"
            value={searchTerm}
            oninput={handleInput}
            onkeydown={handleKeyDown}
            onfocus={() => isOpen = true}
            {placeholder}
            {disabled}
            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 {error ? 'ring-rose-500' : 'ring-slate-100 dark:ring-slate-700'} rounded-xl pl-12 pr-10 py-2.5 font-medium tracking-tight text-sm focus:ring-2 focus:ring-amber-500/50 transition-all outline-none placeholder:text-slate-400 text-slate-800 dark:text-white"
        />

        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
            <svg class="w-4 h-4 transition-transform duration-200 {isOpen ? 'rotate-180' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {#if isOpen && !disabled}
            <div class="absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 origin-top">
                <ul class="max-h-60 overflow-y-auto p-1 custom-scrollbar">
                    {#if filteredOptions.length === 0}
                        <li class="px-4 py-3 text-sm text-slate-500 flex items-center gap-3">
                            <svg class="size-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Luar Negeri / Tidak dalam daftar. Tekan enter untuk menyimpan.</span>
                        </li>
                    {:else}
                        {#each filteredOptions as option, i}
                            <li>
                                <button
                                    type="button"
                                    class="w-full text-left px-4 py-2.5 text-sm rounded-xl transition-all flex items-center justify-between
                                    {i === highlightedIndex || value === (option.code || option.id) ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'}"
                                    onclick={() => selectOption(option)}
                                    onmouseenter={() => highlightedIndex = i}
                                >
                                    <span class="capitalize">{(option.name || option.label).toLowerCase()}</span>
                                    {#if value === (option.code || option.id)}
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    {/if}
                                </button>
                            </li>
                        {/each}
                    {/if}
                </ul>
            </div>
        {/if}
    </div>
    
    {#if error}
        <p class="text-[10px] font-semibold text-rose-500 ml-1 animate-in fade-in slide-in-from-top-1">
            {error}
        </p>
    {/if}
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #1e293b;
    }
</style>
