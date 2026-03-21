<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-blue-dark">Usuários</h2>
            <p class="text-sm text-gray-400 mt-1">Gerencie os usuários do sistema</p>
        </div>

        <x-modal title="Novo Usuário" size="max-w-lg">
            <x-slot name="trigger">
                <x-button class="px-4" dusk="open-create-user-modal">
                    <x-lucide-plus class="size-4" />
                    Novo
                </x-button>
            </x-slot>

            <x-card class="bg-white">
                <form action="{{ route('users.store') }}" method="POST" class="space-y-6 w-full">
                    @csrf

                    <div class="grid grid-cols-1 gap-6">
                        <x-form.input label="Nome" name="name" input-id="create-user-name" />
                        <x-form.input label="E-mail" name="email" input-id="create-user-email" type="email" />
                        <x-form.input label="Senha" name="password" input-id="create-user-password" type="password" />
                        <x-form.input label="Confirmar Senha" name="password_confirmation" input-id="create-user-password-confirmation" type="password" />

                        <div class="group">
                            <label for="role" class="uppercase text-xss block text-gray-400 group-focus-within:text-indigo-500">
                                Função
                            </label>
                            <select
                                name="role"
                                id="role"
                                class="h-10 w-full text-sm text-gray-500 border-0 border-b border-gray-200 bg-transparent focus:outline-none focus:border-indigo-500 focus:border-b-2 transition-colors"
                            >
                                <option value="member">Membro</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <x-button type="button" variant="ghost" @click="$dispatch('close-modal')">
                            Cancelar
                        </x-button>
                        <x-button type="submit">
                            Salvar
                        </x-button>
                    </div>
                </form>
            </x-card>
        </x-modal>
    </div>

    {{-- Tabela de Usuários --}}
    <section class="border-dotted border border-gray-200 rounded-lg">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-4 text-start text-sm text-gray-300">Nome</th>
                    <th class="px-4 py-4 text-start text-sm text-gray-300">E-mail</th>
                    <th class="px-4 py-4 text-start text-sm text-gray-300">Função</th>
                    <th class="px-4 py-4 w-10"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t border-dotted border-gray-200">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$user->name" />
                                <span class="text-sm">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-500">{{ $user->email }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span @class([
                                'inline-flex items-center px-2 py-1 text-xs font-medium rounded',
                                'bg-indigo-100 text-indigo-700' => $user->role === 'admin',
                                'bg-gray-100 text-gray-700' => $user->role === 'member',
                            ])>
                                {{ $user->role === 'admin' ? 'Administrador' : 'Membro' }}
                            </span>
                        </td>
                        <td class="flex gap-2 px-4 py-4">
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.update', $user) }}">
                                    @method('PATCH')
                                    @csrf
                                    <input type="hidden" name="role" value="{{ $user->role === 'admin' ? 'member' : 'admin' }}">
                                    <button
                                        type="submit"
                                        class="flex items-center justify-center cursor-pointer size-7 bg-indigo-100 text-indigo-600 rounded"
                                        title="{{ $user->role === 'admin' ? 'Tornar Membro' : 'Tornar Administrador' }}"
                                    >
                                        <x-lucide-repeat class="size-3" />
                                    </button>
                                </form>

                                <div x-data="{
                                    confirmDelete() {
                                        return confirm('Tem certeza que deseja deletar este usuário?');
                                    }
                                }">
                                    <form
                                        method="POST"
                                        action="{{ route('users.destroy', $user) }}"
                                        @submit="if (!confirmDelete()) $event.preventDefault()"
                                    >
                                        @method('DELETE')
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex items-center justify-center cursor-pointer size-7 bg-red-100 text-red-600 rounded"
                                            title="Deletar"
                                        >
                                            <x-lucide-trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-gray-300">
                            Nenhum usuário cadastrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
