@blaze

@php
$attributes = ['@close' => 'something'];
$variant = 'default';
$position = 'bottom';
$classes = match ($variant) {
    default => match($position) {
        'bottom' => 'p-6',
        default => 'p-4',
    },
};
[ $styleAttributes , $attributes ] = [1, 2];
echo "something";
@endphp
