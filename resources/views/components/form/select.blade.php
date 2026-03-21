@props([
    'label' => null,
    'name',
])

@error($name)
    @php
        $hasError = true;
    @endphp
@else
    @php
        $hasError = false;
    @endphp
@enderror

<div class="group">
    @if($label)
        <label for={{ $name }} class="uppercase transition-colors text-xss block {{ $hasError ? 'text-red-500' : 'text-gray-400 group-focus-within:text-indigo-500' }}">{{ $label }}</label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="h-10 w-full text-sm text-gray-500 bg-transparent border-0 border-b border-gray-200 focus:outline-none focus:border-indigo-500 focus:border-b-2 transition-colors"
        {{ $attributes }}
    >
        {{ $slot }}
    </select>

    @error($name)
        <div class="text-red-500 text-xs mt-2 flex gap-1">
            <x-icon.circle-alert size='16' />
            {{ $message }}
        </div>
    @enderror

</div>
