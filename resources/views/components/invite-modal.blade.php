@props(['workspace'])

<x-modal title="Convidar membro" size="max-w-md">
    <x-slot name="trigger">
        <x-button>
            <x-icon name="lucide-user-plus" class="size-4" />
            <span>Convidar</span>
        </x-button>
    </x-slot>

    <x-card class="bg-white">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Convidar membro</h2>

        <form method="POST" action="{{ route('workspace.members.invite', $workspace) }}">
            @csrf

            <div class="space-y-4">
                <x-form.input
                    label="E-mail"
                    name="email"
                    type="email"
                    required
                    placeholder="email@exemplo.com"
                />

                <x-form.select
                    label="Função"
                    name="role"
                    id="invite-role"
                    required
                >
                    @foreach (\App\Enums\WorkspaceRole::cases() as $role)
                        @if($role->value !== 'owner')
                            <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
                        @endif
                    @endforeach
                </x-form.select>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <x-button type="button" variant="ghost" @click="$dispatch('close-modal')">
                    Cancelar
                </x-button>
                <x-button type="submit">
                    Enviar convite
                </x-button>
            </div>
        </form>
    </x-card>
</x-modal>
