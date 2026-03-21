@php
    $initials = collect(explode(' ', $name))
        ->map(fn($word) => substr($word, 0, 1))
        ->slice(0, 2)
        ->join('');
@endphp

<div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-sm">
    {{ strtoupper($initials) }}
</div>
