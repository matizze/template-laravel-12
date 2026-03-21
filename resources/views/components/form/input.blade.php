@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'inputId' => null,
])

@php
    $id = $inputId ?? $name;
    $hasError = $errors->has($name);
@endphp

<div class="group">
    <label for="{{ $id }}" class="block text-xss uppercase transition-colors {{ $hasError ? 'text-red-500' : 'text-gray-400 group-focus-within:text-indigo-500' }}">
        {{ $label }}
    </label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        class="h-10 w-full text-sm text-gray-500 border-0 border-b border-gray-200 placeholder:text-sm placeholder:text-gray-300 focus:border-b-2 focus:border-indigo-500 focus:outline-none transition-colors"
        {{ $attributes }}
    />

    @error($name)
        <div class="mt-2 flex gap-1 text-xs text-red-500">
            <x-icon.circle-alert size='16' />
            {{ $message }}
        </div>
    @enderror
</div>
