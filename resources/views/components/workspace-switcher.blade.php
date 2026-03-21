@php
    $workspaces = auth()->user()->workspaces()->get();
    $currentWorkspace = \App\Models\Workspace::current();
@endphp

<div x-data="{ open: false }" class="border-b border-gray-200 p-4 dark:border-gray-700">
    <button dusk="workspace-switcher-toggle" @click="open = !open" class="flex w-full items-center justify-between rounded p-2 hover:bg-gray-50 dark:hover:bg-gray-800">
        <div class="text-left">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $currentWorkspace?->name ?? 'Carregando...' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ auth()->user()->roleIn($currentWorkspace)?->label() }}
            </p>
        </div>
        <x-icon.chevron-down class="h-4 w-4 text-gray-400" />
    </button>

    <div dusk="workspace-switcher-dropdown" x-show="open" @click.outside="open = false" class="mt-2 space-y-1">
        @forelse ($workspaces as $workspace)
            <form method="POST" action="{{ route('workspace.switch', $workspace) }}" class="w-full">
                @csrf
                <button
                    dusk="workspace-item-{{ $workspace->id }}"
                    type="submit"
                    @class([
                        'w-full rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                        'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' => $currentWorkspace?->id === $workspace->id,
                    ])
                >
                    {{ $workspace->name }}
                </button>
            </form>
        @empty
            <p class="px-3 py-2 text-sm text-gray-500">Nenhum workspace</p>
        @endforelse

        <button
            dusk="new-workspace-btn"
            @click="open = false; window.dispatchEvent(new CustomEvent('open-create-workspace-modal'))"
            class="mt-2 flex w-full items-center gap-2 rounded border-t border-gray-200 px-3 py-2 pt-2 text-left text-sm text-blue-600 hover:bg-blue-50 dark:border-gray-700 dark:text-blue-400 dark:hover:bg-blue-900/20"
        >
            <x-lucide-plus class="h-4 w-4" />
            Novo workspace
        </button>
    </div>
</div>

<x-create-workspace-modal />
