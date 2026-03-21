@props([
    'label',
    'name',
    'value' => null,
    'rows' => 3,
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
    <label for={{ $name }} class="uppercase transition-colors text-xss block {{ $hasError ? 'text-red-500' : 'text-gray-400 group-focus-within:text-indigo-500' }}">{{ $label }}</label>
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        class="w-full text-sm text-gray-500 border-0 border-b border-gray-200 placeholder:text-gray-300 placeholder:text-sm focus:outline-none focus:border-indigo-500 focus:border-b-2 transition-colors"
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <div class="text-red-500 text-xs mt-2 flex gap-1">
            <x-icon.circle-alert size='16' />
            {{ $message }}
        </div>
    @enderror

</div>
