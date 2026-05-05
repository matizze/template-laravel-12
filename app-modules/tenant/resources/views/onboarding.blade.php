<x-layout.app>
    <x-slot:title>{{ $canCreate ? 'Criar tenant' : 'Aguarde acesso' }}</x-slot:title>

    <div class="flex items-center justify-center h-full bg-white">
        <div class="w-full max-w-md px-8">
            <div class="mb-10 text-center">
                <img
                    src="/brand/nav-header.svg"
                    class="h-8 w-auto mx-auto mb-8"
                    alt="{{ config('app.name') }}"
                />

                @if ($canCreate)
                    <h1 class="text-2xl font-bold text-gray-900">Bem-vindo, {{ auth()->user()->name }}!</h1>
                    <p class="text-sm text-gray-500 mt-2">Vamos criar o seu primeiro tenant para começar.</p>
                @else
                    <h1 class="text-2xl font-bold text-gray-900">Aguardando acesso, {{ auth()->user()->name }}.</h1>
                    <p class="text-sm text-gray-500 mt-2">
                        Sua conta foi criada. Aguarde um administrador atribuir-lhe acesso a um tenant.
                    </p>
                @endif
            </div>

            @if ($canCreate)
                <x-card class="flex-col space-y-8">
                    <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-6">
                        @csrf

                        <x-form.input
                            label="Nome do tenant"
                            name="name"
                            :value="$defaultName"
                            placeholder="{{ $defaultName }}"
                            required
                            autofocus
                        />

                        <x-form.textarea
                            label="Descrição (opcional)"
                            name="description"
                            placeholder="Para que serve este tenant?"
                            rows="3"
                        />

                        <x-button type="submit" class="w-full">
                            Criar tenant
                        </x-button>
                    </form>
                </x-card>
            @else
                <x-card class="flex-col items-center text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="secondary">Sair</x-button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-layout.app>
