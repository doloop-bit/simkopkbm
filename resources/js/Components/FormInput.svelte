<script>
    let { 
        label, 
        name, 
        value = $bindable(), 
        type = 'text', 
        error = '', 
        placeholder = '', 
        required = false,
        maxlength = null,
        icon = null,
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
    
    <div class="relative group">
        {#if icon}
            <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-500 transition-colors">
                {@render icon()}
            </div>
        {/if}
        
        <input
            {type}
            {name}
            id={name}
            bind:value
            {placeholder}
            {required}
            {maxlength}
            class="w-full bg-slate-50 dark:bg-slate-800 border-none ring-1 {error ? 'ring-rose-500' : 'ring-slate-100 dark:ring-slate-700'} rounded-xl {icon ? 'pl-14' : 'px-5'} py-2.5 font-medium tracking-tight text-sm focus:ring-2 focus:ring-amber-500/50 transition-all outline-none"
        />
    </div>
    
    {#if error}
        <p class="text-[10px] font-semibold text-rose-500 ml-1 animate-in fade-in slide-in-from-top-1">
            {error}
        </p>
    {/if}
</div>
