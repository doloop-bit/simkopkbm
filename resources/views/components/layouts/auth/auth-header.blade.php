@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $title }}</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $description }}</p>
</div>
