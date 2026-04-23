<script>
    let { 
        label, 
        name, 
        value = $bindable(), 
        options = [], 
        error = '', 
        placeholder = 'Pilih Opsi...', 
        required = false,
        disabled = false,
        class: className = ''
    } = $props();
</script>

<div class="space-y-2 {className}">
    {#if label}
        <label for={name} class="text-xs font-semibold text-slate-500 tracking-wide ml-1">
            {label}
            {#if required}
                <span class="text-rose-500">*</span>
            {/if}
        </label>
    {/if}
    
    <div class="relative">
        <select
            {name}
            id={name}
            bind:value
            {required}
            {disabled}
            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 {error ? 'ring-rose-500' : 'ring-slate-100 dark:ring-slate-700'} rounded-xl px-5 py-2.5 font-medium tracking-tight text-sm focus:ring-2 focus:ring-amber-500/50 transition-all appearance-none outline-none disabled:opacity-50 cursor-pointer"
        >
            <option value="">{placeholder}</option>
            {#each options as option}
                {@const labelText = (option.name || option.label || (typeof option === 'object' ? option.text : option)).toLowerCase()}
                <option value={option.code || option.id || (typeof option === 'object' ? option.value : option)}>
                    {labelText.replace(/\b\w/g, l => l.toUpperCase())}
                </option>
            {/each}
        </select>
        
        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
    
    {#if error}
        <p class="text-[10px] font-semibold text-rose-500 ml-1 animate-in fade-in slide-in-from-top-1">
            {error}
        </p>
    {/if}
</div>
