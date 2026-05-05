<div class="space-y-8 w-full">
    {{-- Tenant Information --}}
    <x-card class="w-full lg:w-2/3">
        <div class="space-y-6 w-full">
            <div>
                <h2 class="text-xl font-bold text-blue-dark">Informações do Tenant</h2>
                <p class="text-sm text-gray-400 mt-1">Atualize o nome, slug e descrição do tenant</p>
            </div>

            <form method="POST" action="{{ route('tenant.settings.update', $tenant) }}" class="space-y-4 w-full">
                @csrf
                @method('PATCH')

                <x-form.input
                    label="Nome"
                    name="name"
                    type="text"
                    :value="$tenant->name"
                    required
                />

                <x-form.input
                    label="Slug"
                    name="slug"
                    type="text"
                    :value="$tenant->slug"
                    required
                />

                <x-form.textarea
                    label="Descrição"
                    name="description"
                    :value="$tenant->description"
                    placeholder="Descrição do tenant"
                />

                <x-button type="submit" variant="default">
                    Salvar
                </x-button>
            </form>
        </div>
    </x-card>

    {{-- Delete Tenant --}}
    <x-card class="w-2/3 border border-feedback-danger/30">
        <div class="space-y-4 w-full">
            <div>
                <h2 class="text-xl font-bold text-feedback-danger">Excluir Tenant</h2>
                <p class="text-sm text-gray-400 mt-1">Exclua este tenant e todos os seus dados</p>
            </div>

            <div class="bg-feedback-danger/10 border border-feedback-danger/30 rounded p-4">
                <p class="text-sm font-semibold text-feedback-danger mb-1">Aviso</p>
                <p class="text-sm text-gray-400">Prossiga com cuidado, esta ação não pode ser desfeita. Todos os membros serão removidos.</p>
            </div>

            <x-modal title="Confirmar exclusão do tenant" size="max-w-lg">
                <x-slot name="trigger">
                    <x-button variant="destructive">
                        Excluir Tenant
                    </x-button>
                </x-slot>

                <x-card class="bg-white">
                    <form method="POST" action="{{ route('tenant.destroy', $tenant) }}" class="space-y-4 w-full">
                        @csrf
                        @method('DELETE')

                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-600">
                                Tem certeza que deseja excluir o tenant "{{ $tenant->name }}"?
                            </h3>
                            <p class="text-sm text-gray-400">
                                Uma vez excluído, todos os membros associados serão permanentemente removidos.
                                Os projetos vinculados serão desassociados, mas não excluídos.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <x-button type="button" variant="ghost" @click="$dispatch('close-modal')">
                                Cancelar
                            </x-button>
                            <x-button type="submit" variant="destructive">
                                Excluir Tenant
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </x-modal>
        </div>
    </x-card>
</div>
