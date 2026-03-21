@props(['workspace'])

<x-modal title="Convidar membro" size="max-w-md">
    <x-slot name="trigger">
        <button
            class="flex items-center gap-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors px-4 py-2"
        >
            <x-icon name="lucide-user-plus" class="size-4" />
            <span>Convidar</span>
        </button>
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
                <button
                    type="button"
                    @click="$dispatch('close-modal')"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Enviar convite
                </button>
            </div>
        </form>
    </x-card>
</x-modal>
