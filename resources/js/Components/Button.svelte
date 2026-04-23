<script>
    let { 
        type = 'button', 
        variant = 'primary', // primary, secondary, ghost, outline, danger
        size = 'md', // sm, md, lg
        loading = false, 
        disabled = false, 
        onclick, 
        class: className = '',
        children
    } = $props();

    const variants = {
        primary: 'bg-amber-500 text-white shadow-lg shadow-amber-500/20 hover:bg-amber-600 active:bg-amber-700',
        secondary: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
        outline: 'bg-transparent border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-amber-500 hover:text-amber-500',
        ghost: 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300',
        danger: 'bg-rose-500 text-white shadow-lg shadow-rose-500/20 hover:bg-rose-600 active:bg-rose-700'
    };

    const sizes = {
        sm: 'px-4 py-1.5 text-xs rounded-full',
        md: 'px-8 py-2.5 text-sm rounded-full',
        lg: 'px-10 py-3.5 text-base rounded-full'
    };
</script>

<button
    {type}
    disabled={disabled || loading}
    {onclick}
    class="inline-flex items-center justify-center gap-2 font-semibold tracking-tight transition-all duration-300 transform active:scale-95 disabled:opacity-50 disabled:active:scale-100 disabled:cursor-not-allowed
    {variants[variant]} {sizes[size]} {className}"
>
    {#if loading}
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    {/if}
    
    <span class="flex items-center gap-2">
        {@render children?.()}
    </span>
</button>
