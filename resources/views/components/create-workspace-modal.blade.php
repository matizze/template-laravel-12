<div
    x-data="{ open: false }"
    x-cloak
    x-on:keydown.window.escape="open = false"
    x-on:close-modal.window="open = false"
    x-on:open-create-workspace-modal.window="open = true"
>
    {{-- Backdrop / Modal --}}
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="open"
        x-on:click.self="open = false"
        class="fixed inset-0 z-30 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title-create-workspace"
    >
        <div
            x-show="open"
            x-transition.scale.duration.200ms
            x-cloak
            @click.stop
            class="relative w-full max-w-md max-h-[90vh] overflow-auto"
            role="document"
        >
            <h2 id="modal-title-create-workspace" class="sr-only">Criar Workspace</h2>

            <x-card class="flex-col bg-white" dusk="create-workspace-modal">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Criar Workspace</h2>

                <form method="POST" action="{{ route('workspace.store') }}" class="space-y-4">
                    @csrf

                    <x-form.input
                        label="Nome"
                        name="name"
                        type="text"
                        required
                        placeholder="Nome do workspace"
                    />

                    <x-form.textarea
                        label="Descrição (opcional)"
                        name="description"
                        placeholder="Descrição do workspace"
                        class="resize-none"
                    />

                    <div class="flex justify-end gap-3 pt-4">
                        <x-button type="button" variant="ghost" @click="open = false">
                            Cancelar
                        </x-button>
                        <x-button type="submit">
                            Criar
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</div>
