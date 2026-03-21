<div
    x-data="{ open: false }"
    x-on:click.away="open = false"
    x-on:keydown.escape="open = false"
    {{ $attributes->class(['relative']) }}
>
    <!-- Trigger Button -->
    <button
        type="button"
        x-on:click="open = !open"
        class="flex items-center justify-start gap-3 w-full rounded"
    >
        <x-avatar :name="auth()->user()->name"/>
        <span class="text-sm font-medium text-left min-w-0">
            <h1 class="text-gray-200 truncate">{{ auth()->user()->name }}</h1>
            <p class="text-gray-300 text-xs truncate">{{ auth()->user()->email }}</p>
        </span>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-0 left-full w-56 bg-gray-500 rounded-lg shadow-lg border border-gray-400/20 overflow-hidden"
    >
        <!-- Header -->
        <div class="px-4 pb-1.5 pt-2.5 border-b border-gray-600/10">
            <p class="text-xss font-semibold text-gray-300 uppercase tracking-wider">Opções</p>
        </div>

        <!-- Menu Items -->
        <div>
            <!-- Configurações -->
            <a
                href="{{ route('settings.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 text-xs text-gray-300 hover:bg-gray-600/40 transition-colors"
            >
                <x-icon name="lucide-settings" class="size-3.5" />
                <span>Configurações</span>
            </a>

            <!-- Sair -->
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button
                    type="submit"
                    class="flex items-center gap-3 w-full text-left px-4 py-2.5 text-xs text-feedback-danger hover:bg-feedback-danger/10 transition-colors"
                >
                    <x-icon name="lucide-log-out" class="size-3.5" />
                    <span>Sair</span>
                </button>
            </form>
        </div>
    </div>
</div>
