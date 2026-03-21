@props([
    'class' => null
])

<div {{ $attributes->class(["flex border border-gray-200 rounded-xl p-7", $class]) }}>
    {{ $slot }}
</div>
