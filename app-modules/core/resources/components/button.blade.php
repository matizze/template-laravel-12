@props([
    'tag' => 'button',
    'variant' => 'default',
])

@php
    $variants = [
        'base' => 'flex gap-2 items-center justify-center h-10 p-3 rounded-md text-sm cursor-pointer',
        'default' => 'bg-gray-600 text-gray-100',
        'ghost' => 'bg-gray-200 text-gray-500',
        'destructive' => 'bg-feedback-danger text-gray-100',
        'destructive-outline' => 'bg-white border border-feedback-danger text-feedback-danger',
    ];

    $key = array_key_exists($variant, $variants) ? $variant : 'default';

    $extra = [];
    if ($tag === 'button' && ! $attributes->has('type')) {
        $extra['type'] = 'button';
    }
@endphp

<{{ $tag }}
    {{ $attributes->merge(['class' => $variants['base'] . ' ' . $variants[$key]])->merge($extra) }}
>
    {{ $slot }}
</{{ $tag }}>
