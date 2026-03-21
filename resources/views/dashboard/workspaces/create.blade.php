<x-layout.dashboard title="Criar Workspace">
    <div class="max-w-lg">
        <h1 class="text-2xl font-bold text-blue-dark mb-6">Criar Workspace</h1>

        <form method="POST" action="{{ route('workspace.store') }}" class="space-y-4">
            @csrf

            <x-form.input
                label="Nome"
                name="name"
                type="text"
                required
                placeholder="Nome do workspace"
            />

            <x-form.input
                label="Slug (opcional)"
                name="slug"
                type="text"
                placeholder="meu-workspace"
            />

            <x-form.textarea
                label="Descrição (opcional)"
                name="description"
                placeholder="Descrição do workspace"
            />

            <div class="flex justify-end gap-3 pt-4">
                <a
                    href="{{ route('dashboard') }}"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Criar Workspace
                </button>
            </div>
        </form>
    </div>
</x-layout.dashboard>
