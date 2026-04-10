@props([
    'value' => null,
    'label' => null,
    'variant' => null, // Manual: primary, success, warning, error, info, neutral
    'size' => 'sm',
    'rounded' => 'md', // Default to md as per user preference
    'glow' => false,
    'icon' => null,
])

@php
    $text = (string) ($value ?? ($label ?? $slot));

    if (!$variant) {
        $variant = match (strtolower(trim($text))) {
            'aktif', 'active', 'lunas', 'selesai', 'verified', 'open', 'terbuka' => 'primary',
            'non aktif', 'inactive', 'ditunda', 'pending', 'closed', 'ditutup' => 'neutral',
            'non-aktif', 'ditolak', 'rejected', 'error', 'batal' => 'error',
            default => 'neutral',
        };
    }

    $baseStyles =
        'inline-flex items-center gap-1.5 font-black uppercase tracking-[0.2em] transition-all duration-300 border h-fit text-[10px]';

    $roundedClass = match ($rounded) {
        'full' => 'rounded-full',
        'lg' => 'rounded-xl',
        default => 'rounded-md',
    };

    $variantClasses = match ($variant) {
        'primary' => [
            'bg' => 'bg-primary/10 dark:bg-primary/25',
            'text' => 'text-primary dark:text-primary-400',
            'border' => 'border-primary/0 dark:border-primary/30',
            'glow' => 'shadow-[0_0_12px_rgba(var(--color-primary-500),0.15)]',
        ],
        'success' => [
            'bg' => 'bg-emerald-500/15 dark:bg-emerald-500/25',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'border' => 'border-emerald-500/20 dark:border-emerald-500/30',
            'glow' => 'shadow-[0_0_12px_rgba(16,185,129,0.15)]',
        ],
        'error' => [
            'bg' => 'bg-rose-500/15 dark:bg-rose-500/25',
            'text' => 'text-rose-600 dark:text-rose-400',
            'border' => 'border-rose-500/20 dark:border-rose-500/30',
            'glow' => 'shadow-[0_0_12px_rgba(244,63,94,0.15)]',
        ],
        'warning' => [
            'bg' => 'bg-amber-500/15 dark:bg-amber-500/25',
            'text' => 'text-amber-600 dark:text-amber-400',
            'border' => 'border-amber-500/20 dark:border-amber-500/30',
            'glow' => 'shadow-[0_0_12px_rgba(245,158,11,0.15)]',
        ],
        'info' => [
            'bg' => 'bg-sky-500/15 dark:bg-sky-500/25',
            'text' => 'text-sky-600 dark:text-sky-400',
            'border' => 'border-sky-500/20 dark:border-sky-500/30',
            'glow' => 'shadow-[0_0_12px_rgba(14,165,233,0.15)]',
        ],
        default => [
            'bg' => 'bg-slate-500/15 dark:bg-slate-500/25',
            'text' => 'text-slate-600 dark:text-slate-400',
            'border' => 'border-slate-500/20 dark:border-slate-500/30',
            'glow' => 'shadow-sm',
        ],
    };

    // Keep padding balanced for the font-black look
    $sizeClasses = match ($size) {
        'xs' => 'px-2 py-0.5',
        'md' => 'px-3.5 py-1.5',
        default => 'px-2.5 py-1',
    };
@endphp

<span
    {{ $attributes->class([
        $baseStyles,
        $roundedClass,
        $variantClasses['bg'],
        $variantClasses['text'],
        $variantClasses['border'],
        $glow ? $variantClasses['glow'] : 'shadow-xs',
        $sizeClasses,
    ]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon"
            class="{{ $size === 'xs' ? 'size-2.5' : ($size === 'md' ? 'size-4' : 'size-3.5') }}" />
    @endif

    <span class="inline-block">{{ $text }}</span>
</span>
