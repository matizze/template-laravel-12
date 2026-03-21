@props([
    'icon' => 'menu',
    'route' => '',
])

@php
    $href = $route ? route($route) : '#';
    $active = $route && request()->routeIs($route);
@endphp

<a
    href="{{ $href }}"
    @class([
        'flex items-center justify-start rounded gap-3 p-3',
        'bg-blue-dark text-gray-100' => $active,
        'bg-gray-600 text-gray-300 hover:bg-gray-500 hover:text-gray-200' => ! $active,
    ])
>
    <x-icon :name="'lucide-' . $icon" class="size-5" />
    <span class="text-sm font-normal">{{ $slot }}</span>
</a>
