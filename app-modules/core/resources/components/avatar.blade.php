@php
    $parts = explode(' ', trim($name));
    $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
@endphp

<div class="flex items-center justify-center size-6 bg-blue-dark text-white rounded-full text-xs shrink-0">
    {{ $initials }}
</div>
