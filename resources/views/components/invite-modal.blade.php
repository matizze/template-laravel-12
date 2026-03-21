@php
    $workspace = \App\Models\Workspace::current();
    $roles = \App\Enums\WorkspaceRole::assignable();
@endphp

<x-modal title="Convidar membro" duskId="invite-modal" listenEvent="open-invite-modal">
    <form method="POST" action="{{ route('members.invite', $workspace) }}" class="space-y-4">
        @csrf

        <x-form.input
            name="email"
            input-id="invite-email"
            label="Email"
            type="email"
            placeholder="usuario@example.com"
            dusk="input-invite-email"
            required
            autofocus
        />

        <div>
            <label for="role" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Função
            </label>
            <select
                dusk="select-invite-role"
                name="role"
                id="role"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            >
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <button
                type="button"
                @click="$dispatch('close-modal')"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Cancelar
            </button>
            <button
                dusk="btn-submit-invite"
                type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
                Enviar convite
            </button>
        </div>
    </form>
</x-modal>
