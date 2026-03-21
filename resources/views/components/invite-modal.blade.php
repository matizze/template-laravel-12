@props(['workspace'])

<div
    x-data="{ open: false }"
    @open-invite-modal.window="open = true"
>
    <button
        @click="open = true"
        class="flex items-center gap-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors px-4 py-2"
    >
        <x-icon name="lucide-user-plus" class="size-4" />
        <span>Convidar</span>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                @click.outside="open = false"
                class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
            >
                <h2 class="text-lg font-bold text-gray-900 mb-4">Convidar membro</h2>

                <form method="POST" action="{{ route('workspace.members.invite', $workspace) }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="invite-email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input
                                type="email"
                                name="email"
                                id="invite-email"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="email@exemplo.com"
                            />
                        </div>

                        <div>
                            <label for="invite-role" class="block text-sm font-medium text-gray-700 mb-1">Função</label>
                            <select
                                name="role"
                                id="invite-role"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            >
                                @foreach (\App\Enums\WorkspaceRole::cases() as $role)
                                    @if($role->value !== 'owner')
                                        <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="open = false"
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
            </div>
        </div>
    </template>
</div>
