<x-layout.dashboard title="Criar usuário">
    <div class="max-w-2xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Criar usuário</h1>
            <p class="text-sm text-gray-500 mt-1">
                Crie um novo usuário global. Um e-mail de definição de senha será enviado para o endereço informado.
            </p>
        </div>

        <x-card class="flex-col space-y-8">
            <form method="POST" action="{{ route('tenant.users.create.store') }}" class="space-y-6">
                @csrf

                <x-form.input
                    label="Nome"
                    name="name"
                    required
                    autofocus
                />

                <x-form.input
                    label="E-mail"
                    name="email"
                    type="email"
                    required
                />

                <x-button type="submit" class="w-full">
                    Criar usuário
                </x-button>
            </form>
        </x-card>
    </div>
</x-layout.dashboard>
