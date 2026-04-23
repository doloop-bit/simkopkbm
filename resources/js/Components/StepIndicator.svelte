<script>
    let { currentStep, totalSteps, steps = [] } = $props();
</script>

<div class="mb-12">
    <div class="flex items-center justify-between relative px-2">
        <!-- Dynamic Progress Timeline -->
        <div class="absolute top-5 left-8 right-8 h-1 bg-slate-100 dark:bg-slate-800 rounded-full">
            <div 
                class="h-full bg-amber-500 transition-all duration-1000 ease-out shadow-[0_0_15px_rgba(245,158,11,0.3)]"
                style="width: {((currentStep - 1) / (totalSteps - 1)) * 100}%"
            ></div>
        </div>

        {#each steps as step, index}
            <div class="relative z-10 flex flex-col items-center group transition-all duration-500">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-500 shadow-sm
                    {index + 1 < currentStep ? 'bg-amber-500 text-white scale-90' : ''}
                    {index + 1 === currentStep ? 'bg-white dark:bg-slate-900 text-amber-500 ring-4 ring-amber-500/10 shadow-lg shadow-amber-500/10 -translate-y-0.5' : ''}
                    {index + 1 > currentStep ? 'bg-slate-50 dark:bg-slate-900 text-slate-300' : ''}">
                    
                    {#if index + 1 < currentStep}
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    {:else if step.icon === 'user'}
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    {:else if step.icon === 'map-pin'}
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {:else if step.icon === 'users'}
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    {:else if step.icon === 'academic'}
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    {/if}
                </div>
                
                <div class="absolute top-14 whitespace-nowrap hidden sm:block text-center">
                    <span class="text-[0.6rem] font-semibold tracking-wider transition-colors duration-500
                        {index + 1 <= currentStep ? 'text-amber-600' : 'text-slate-400'}">
                        {step.label}
                    </span>
                </div>
            </div>
        {/each}
    </div>
</div>
