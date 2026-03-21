@props(['workspaces' => collect(), 'currentWorkspace' => null])

<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition-colors w-full px-3 py-2 rounded-lg hover:bg-white/5"
        dusk="workspace-switcher"
    >
        <x-icon name="lucide-layers" class="size-4" />
        <span class="truncate flex-1 text-left">
            {{ $currentWorkspace?->name ?? 'Selecionar Workspace' }}
        </span>
        <x-icon name="lucide-chevron-down" class="size-3" />
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute left-0 top-full mt-1 w-64 bg-gray-800 border border-gray-700 rounded-lg shadow-lg z-50"
    >
        <div class="p-2">
            @foreach($workspaces as $workspace)
                <form method="POST" action="{{ route('workspace.switch', $workspace) }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex items-center gap-2 w-full px-3 py-2 text-sm rounded-lg transition-colors
                            {{ $currentWorkspace?->id === $workspace->id ? 'bg-blue-600/20 text-blue-400' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <x-icon name="lucide-folder" class="size-4" />
                        <span class="truncate">{{ $workspace->name }}</span>
                        @if($currentWorkspace?->id === $workspace->id)
                            <x-icon name="lucide-check" class="size-3 ml-auto" />
                        @endif
                    </button>
                </form>
            @endforeach
        </div>

        <div class="border-t border-gray-700 p-2">
            <a
                href="{{ route('workspace.create') }}"
                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/5 rounded-lg transition-colors"
                dusk="open-create-workspace-link"
            >
                <x-icon name="lucide-plus" class="size-4" />
                <span>Criar Workspace</span>
            </a>
        </div>
    </div>
</div>
