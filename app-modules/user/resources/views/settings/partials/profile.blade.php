<div class="space-y-8 w-full">
    {{-- Profile Information --}}
    <x-card class="w-2/3">
        <div class="space-y-6 w-full">
            <div>
                <h2 class="text-xl font-bold text-blue-dark">Informações do perfil</h2>
                <p class="text-sm text-gray-400 mt-1">Atualize seu nome e endereço de e-mail</p>
            </div>

            <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4 w-full">
                @csrf
                @method('PATCH')

                <x-form.input
                    label="Nome"
                    name="name"
                    type="text"
                    :value="auth()->user()->name"
                    required
                />

                <x-form.input
                    label="Endereço de e-mail"
                    name="email"
                    type="email"
                    :value="auth()->user()->email"
                    required
                />

                <x-button type="submit" variant="default">
                    Salvar
                </x-button>
            </form>
        </div>
    </x-card>

    {{-- Delete Account --}}
    <x-card class="w-2/3 border border-feedback-danger/30">
        <div class="space-y-4 w-full">
            <div>
                <h2 class="text-xl font-bold text-feedback-danger">Deletar conta</h2>
                <p class="text-sm text-gray-400 mt-1">Delete sua conta e todos os seus recursos</p>
            </div>

            <div class="bg-feedback-danger/10 border border-feedback-danger/30 rounded p-4">
                <p class="text-sm font-semibold text-feedback-danger mb-1">Aviso</p>
                <p class="text-sm text-gray-400">Prossiga com cuidado, esta ação não pode ser desfeita.</p>
            </div>

            <x-modal title="Confirmar exclusão de conta" size="max-w-lg">
                <x-slot name="trigger">
                    <x-button variant="destructive" dusk="open-delete-modal">
                        Deletar conta
                    </x-button>
                </x-slot>

                <x-card class="bg-white">
                    <form method="POST" action="{{ route('settings.account.destroy') }}" class="space-y-4 w-full">
                        @csrf
                        @method('DELETE')

                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-600">
                                Tem certeza que deseja deletar sua conta?
                            </h3>
                            <p class="text-sm text-gray-400">
                                Uma vez que sua conta for deletada, todos os seus recursos e dados também serão permanentemente deletados.
                                Por favor, digite sua senha para confirmar que deseja deletar permanentemente sua conta.
                            </p>
                        </div>

                        <x-form.input
                            label="Senha"
                            name="password"
                            type="password"
                            required
                            placeholder="Digite sua senha para confirmar"
                        />

                        <div class="flex gap-2">
                            <x-button type="button" variant="ghost" @click="$dispatch('close-modal')">
                                Cancelar
                            </x-button>
                            <x-button type="submit" variant="destructive" dusk="modal-confirm-delete">
                                Deletar conta
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </x-modal>
        </div>
    </x-card>
</div>
