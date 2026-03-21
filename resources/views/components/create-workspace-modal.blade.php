<div
    x-data="{ open: false }"
    @open-create-workspace.window="open = true"
>
    <button
        @click="open = true"
        class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition-colors px-3 py-2 rounded-lg hover:bg-white/5"
        dusk="open-create-workspace-modal"
    >
        <x-icon name="lucide-plus" class="size-4" />
        <span>Novo Workspace</span>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            dusk="create-workspace-modal"
        >
            <div
                @click.outside="open = false"
                class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
            >
                <h2 class="text-lg font-bold text-gray-900 mb-4">Criar Workspace</h2>

                <form method="POST" action="{{ route('workspace.store') }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Nome do workspace"
                            />
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Descrição do workspace"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="open = false"
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
            </div>
        </div>
    </template>
</div>
