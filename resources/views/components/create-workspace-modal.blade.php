<x-modal title="Criar Workspace" size="max-w-md">
    <x-slot name="trigger">
        <button
            class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition-colors px-3 py-2 rounded-lg hover:bg-white/5"
            dusk="open-create-workspace-modal"
        >
            <x-icon name="lucide-plus" class="size-4" />
            <span>Novo Workspace</span>
        </button>
    </x-slot>

    <x-card class="bg-white" dusk="create-workspace-modal">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Criar Workspace</h2>

        <form method="POST" action="{{ route('workspace.store') }}">
            @csrf

            <div class="space-y-4">
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
                />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button
                    type="button"
                    @click="$dispatch('close-modal')"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Criar
                </button>
            </div>
        </form>
    </x-card>
</x-modal>
