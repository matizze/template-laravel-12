<x-modal title="Criar novo workspace" duskId="create-workspace-modal" listenEvent="open-create-workspace-modal">
    <form method="POST" action="{{ route('workspace.store') }}" class="space-y-4">
        @csrf

        <x-form.input
            name="name"
            input-id="workspace-name"
            label="Nome do workspace"
            placeholder="Meu Workspace"
            dusk="input-workspace-name"
            required
            autofocus
        />

        <x-form.input
            name="slug"
            input-id="workspace-slug"
            label="URL slug"
            placeholder="meu-workspace"
            dusk="input-workspace-slug"
            required
        />

        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Descrição (opcional)
            </label>
            <textarea
                dusk="input-workspace-description"
                name="description"
                id="description"
                rows="3"
                placeholder="Descrição do workspace..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
            ></textarea>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <button
                type="button"
                @click="$dispatch('close-modal')"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Cancelar
            </button>
            <button
                dusk="btn-create-workspace"
                type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
                Criar workspace
            </button>
        </div>
    </form>
</x-modal>
