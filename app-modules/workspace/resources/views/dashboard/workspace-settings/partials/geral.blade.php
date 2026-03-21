<div class="space-y-8 w-full">
    {{-- Workspace Information --}}
    <x-card class="w-full lg:w-2/3">
        <div class="space-y-6 w-full">
            <div>
                <h2 class="text-xl font-bold text-blue-dark">Informações do Workspace</h2>
                <p class="text-sm text-gray-400 mt-1">Atualize o nome, slug e descrição do workspace</p>
            </div>

            <form method="POST" action="{{ route('workspace.settings.update', $workspace) }}" class="space-y-4 w-full">
                @csrf
                @method('PATCH')

                <x-form.input
                    label="Nome"
                    name="name"
                    type="text"
                    :value="$workspace->name"
                    required
                />

                <x-form.input
                    label="Slug"
                    name="slug"
                    type="text"
                    :value="$workspace->slug"
                    required
                />

                <x-form.textarea
                    label="Descrição"
                    name="description"
                    :value="$workspace->description"
                    placeholder="Descrição do workspace"
                />

                <x-button type="submit" variant="default">
                    Salvar
                </x-button>
            </form>
        </div>
    </x-card>

    {{-- Delete Workspace --}}
    <x-card class="w-2/3 border border-feedback-danger/30">
        <div class="space-y-4 w-full">
            <div>
                <h2 class="text-xl font-bold text-feedback-danger">Excluir Workspace</h2>
                <p class="text-sm text-gray-400 mt-1">Exclua este workspace e todos os seus dados</p>
            </div>

            <div class="bg-feedback-danger/10 border border-feedback-danger/30 rounded p-4">
                <p class="text-sm font-semibold text-feedback-danger mb-1">Aviso</p>
                <p class="text-sm text-gray-400">Prossiga com cuidado, esta ação não pode ser desfeita. Todos os membros e convites serão removidos.</p>
            </div>

            <x-modal title="Confirmar exclusão do workspace" size="max-w-lg">
                <x-slot name="trigger">
                    <x-button variant="destructive">
                        Excluir Workspace
                    </x-button>
                </x-slot>

                <x-card class="bg-white">
                    <form method="POST" action="{{ route('workspace.destroy', $workspace) }}" class="space-y-4 w-full">
                        @csrf
                        @method('DELETE')

                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-600">
                                Tem certeza que deseja excluir o workspace "{{ $workspace->name }}"?
                            </h3>
                            <p class="text-sm text-gray-400">
                                Uma vez excluído, todos os membros e convites associados serão permanentemente removidos.
                                Os projetos vinculados serão desassociados, mas não excluídos.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-button type="button" variant="ghost" @click="$dispatch('close-modal')">
                                Cancelar
                            </x-button>
                            <x-button type="submit" variant="destructive">
                                Excluir Workspace
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </x-modal>
        </div>
    </x-card>
</div>
