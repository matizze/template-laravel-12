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
                class="resize-none"
            />

            <div class="flex justify-end gap-3 pt-4">
                <x-button tag="a" href="{{ route('dashboard') }}" variant="ghost">
                    Cancelar
                </x-button>
                <x-button type="submit">
                    Criar Workspace
                </x-button>
            </div>
        </form>
    </div>
</x-layout.dashboard>
