<script>
    let { 
        label, 
        name, 
        value = $bindable(), 
        options = [], 
        error = '', 
        required = false,
        disabled = false,
        class: className = ''
    } = $props();

    function selectOption(optionValue) {
        if (!disabled) {
            value = optionValue;
        }
    }
</script>

<div class="space-y-2 {className}">
    {#if label}
        <span class="text-xs font-semibold text-slate-500 tracking-wide ml-1">
            {label}
            {#if required}
                <span class="text-rose-500">*</span>
            {/if}
        </span>
    {/if}
    
    <div class="flex p-1 bg-slate-50 dark:bg-slate-800 rounded-xl ring-1 {error ? 'ring-rose-500' : 'ring-slate-100 dark:ring-slate-700'}">
        {#each options as option}
            <button
                type="button"
                {disabled}
                onclick={() => selectOption(option.id)}
                class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold transition-all duration-300 flex items-center justify-center gap-2
                {value === option.id 
                    ? 'bg-white dark:bg-slate-700 text-amber-600 shadow-sm ring-1 ring-slate-100 dark:ring-slate-600' 
                    : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'}"
            >
                {#if option.id === 'L'}
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 100-8 4 4 0 000 8zM12 14c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                {:else if option.id === 'P'}
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                {/if}
                {option.name}
            </button>
        {/each}
    </div>
    
    {#if error}
        <p class="text-[10px] font-semibold text-rose-500 ml-1 animate-in fade-in slide-in-from-top-1">
            {error}
        </p>
    {/if}
</div>
