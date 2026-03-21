<x-card class="w-2/3">
    <div class="space-y-6 w-full">
        <div>
            <h2 class="text-xl font-bold text-blue-dark">Atualizar senha</h2>
            <p class="text-sm text-gray-400 mt-1">
                Certifique-se de que sua conta está usando uma senha longa e aleatória para se manter segura
            </p>
        </div>

        <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4 w-full">
            @csrf
            @method('PATCH')

            <x-form.input
                label="Senha atual"
                name="current_password"
                type="password"
                required
            />

            <x-form.input
                label="Nova senha"
                name="password"
                type="password"
                required
            />

            <x-form.input
                label="Confirmar senha"
                name="password_confirmation"
                type="password"
                required
            />

            <x-button type="submit" variant="default">
                Salvar senha
            </x-button>
        </form>
    </div>
</x-card>
