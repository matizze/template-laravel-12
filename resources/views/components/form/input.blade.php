@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'inputId' => null,
])

@php $id = $inputId ?? $name; @endphp

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
    <label for="{{ $id }}" class="uppercase transition-colors text-xss block {{ $hasError ? 'text-red-500' : 'text-gray-400 group-focus-within:text-indigo-500' }}">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        class="h-10 w-full text-sm text-gray-500 border-0 border-b border-gray-200 placeholder:text-gray-300 placeholder:text-sm focus:outline-none focus:border-indigo-500 focus:border-b-2 transition-colors"
        {{ $attributes }}
    />

    @error($name)
        <div class="text-red-500 text-xs mt-2 flex gap-1">
            <x-icon.circle-alert size='16' />
            {{ $message }}
        </div>
    @enderror

</div>
