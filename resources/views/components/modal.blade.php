@props(['title' => 'Modal Title', 'size' => 'max-w-lg', 'duskId' => null, 'listenEvent' => null])

<div
    x-data="{ open: false }"
    x-cloak
    x-on:keydown.window.escape="open = false"
    x-on:close-modal.window="open = false"
    @if($listenEvent) x-on:{{ $listenEvent }}.window="open = true" @endif
>
    @isset($trigger)
        <div
            class="inline-block"
            role="button"
            tabindex="0"
            x-on:click="open = true"
            x-on:keydown.enter.prevent="open = true"
            aria-controls="modal-{{ md5($title) }}"
            x-bind:aria-expanded="open.toString()"
        >
            {{ $trigger }}
        </div>
    @endisset

    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="open"
        x-on:click.self="open = false"
        class="fixed inset-0 z-30 flex items-center justify-center bg-black/60 p-4 backdrop-blur-md"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title-{{ md5($title) }}"
        id="modal-{{ md5($title) }}"
        @if($duskId) dusk="{{ $duskId }}" @endif
    >
        <div
            x-show="open"
            x-transition.scale.duration.200ms
            @click.stop
            class="relative w-full {{ $size }} max-h-[90vh] overflow-auto"
            role="document"
        >
            {{-- Visually-hidden title for accessibility --}}
            <h2 id="modal-title-{{ md5($title) }}" class="sr-only">{{ $title }}</h2>

            {{ $slot }}
        </div>
    </div>
</div>
