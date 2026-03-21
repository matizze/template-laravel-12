@props(['workspaces' => collect(), 'currentWorkspace' => null])

<div
    x-data="{ open: false }"
    x-on:click.away="open = false"
    x-on:keydown.escape="open = false"
    class="relative"
>
    <!-- Trigger Button -->
    <button
        type="button"
        x-on:click="open = !open"
        class="flex items-center gap-3 w-full px-3 py-2 rounded-lg hover:bg-white/5 transition-colors"
        aria-label="Alternar workspace"
        aria-haspopup="listbox"
        x-bind:aria-expanded="open.toString()"
        dusk="workspace-switcher"
    >
        <x-icon name="lucide-layers" class="size-4 text-gray-300" />
        <span class="text-sm text-gray-200 truncate flex-1 text-left">
            {{ $currentWorkspace?->name ?? 'Selecionar Workspace' }}
        </span>
        <x-icon name="lucide-chevron-down" class="size-3 text-gray-300" />
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
        class="absolute left-0 top-full mt-1 w-56 bg-gray-500 rounded-lg shadow-lg border border-gray-400/20 overflow-hidden z-50"
    >
        <!-- Header -->
        <div class="px-4 pb-1.5 pt-2.5 border-b border-gray-600/10">
            <p class="text-xss font-semibold text-gray-300 uppercase tracking-wider">Workspaces</p>
        </div>

        <!-- Workspace List -->
        <div>
            @foreach($workspaces as $workspace)
                <form method="POST" action="{{ route('workspace.switch', $workspace) }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-xs transition-colors
                            {{ $currentWorkspace?->id === $workspace->id
                                ? 'text-indigo-300 bg-indigo-500/10'
                                : 'text-gray-300 hover:bg-gray-600/40' }}"
                    >
                        <x-icon name="lucide-folder" class="size-3.5" />
                        <span class="truncate">{{ $workspace->name }}</span>
                        @if($currentWorkspace?->id === $workspace->id)
                            <x-icon name="lucide-check" class="size-3 ml-auto" />
                        @endif
                    </button>
                </form>
            @endforeach
        </div>

        <!-- Configurações do Workspace Atual -->
        @if($currentWorkspace)
            <div class="border-t border-gray-600/10">
                <a
                    href="{{ route('workspace.settings.show', $currentWorkspace) }}"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-xs text-gray-300 hover:bg-gray-600/40 transition-colors"
                >
                    <x-icon name="lucide-settings" class="size-3.5" />
                    <span>Configurações</span>
                </a>
            </div>
        @endif

        <!-- Criar Workspace -->
        <div class="border-t border-gray-600/10">
            <button
                type="button"
                @click="open = false; $dispatch('open-create-workspace-modal')"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-xs text-gray-300 hover:bg-gray-600/40 transition-colors"
                dusk="open-create-workspace-link"
            >
                <x-icon name="lucide-plus" class="size-3.5" />
                <span>Criar Workspace</span>
            </button>
        </div>
    </div>

    <x-workspace::create-workspace-modal />
</div>
