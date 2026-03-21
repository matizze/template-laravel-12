<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-blue-dark">Membros</h2>
            <p class="text-sm text-gray-400 mt-1">Gerencie os membros deste workspace</p>
        </div>

        @can('manageMembers', $workspace)
            <x-invite-modal :workspace="$workspace" />
        @endcan
    </div>

    {{-- Tabela de Membros --}}
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
                @forelse ($members as $membership)
                    <tr class="border-t border-dotted border-gray-200">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$membership->user->name" />
                                <span class="text-sm">{{ $membership->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-500">{{ $membership->user->email }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span @class([
                                'inline-flex items-center px-2 py-1 text-xs font-medium rounded',
                                'bg-indigo-100 text-indigo-700' => $membership->role === \App\Enums\WorkspaceRole::Owner,
                                'bg-blue-100 text-blue-700' => $membership->role === \App\Enums\WorkspaceRole::Admin,
                                'bg-gray-100 text-gray-700' => $membership->role === \App\Enums\WorkspaceRole::Member,
                                'bg-gray-50 text-gray-500' => $membership->role === \App\Enums\WorkspaceRole::Viewer,
                            ])>
                                {{ ucfirst($membership->role->value) }}
                            </span>
                        </td>
                        <td class="flex gap-2 px-4 py-4">
                            @can('manageMembers', $workspace)
                                @if($membership->role !== \App\Enums\WorkspaceRole::Owner)
                                    {{-- Alterar função --}}
                                    <form method="POST" action="{{ route('workspace.members.updateRole', [$workspace, $membership->user]) }}">
                                        @method('PUT')
                                        @csrf
                                        <x-form.select
                                            name="role"
                                            aria-label="Alterar função de {{ $membership->user->name }}"
                                            x-on:change="$el.form.submit()"
                                        >
                                            @foreach (\App\Enums\WorkspaceRole::cases() as $role)
                                                @if($role !== \App\Enums\WorkspaceRole::Owner)
                                                    <option value="{{ $role->value }}" {{ $membership->role === $role ? 'selected' : '' }}>
                                                        {{ ucfirst($role->value) }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </x-form.select>
                                    </form>

                                    {{-- Remover membro --}}
                                    <div x-data="{
                                        confirmRemove() {
                                            return confirm('Tem certeza que deseja remover este membro?');
                                        }
                                    }">
                                        <form
                                            method="POST"
                                            action="{{ route('workspace.members.remove', [$workspace, $membership->user]) }}"
                                            @submit="if (!confirmRemove()) $event.preventDefault()"
                                        >
                                            @method('DELETE')
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex items-center justify-center cursor-pointer size-7 bg-red-100 text-red-600 rounded"
                                                title="Remover membro"
                                                aria-label="Remover {{ $membership->user->name }}"
                                            >
                                                <x-icon name="lucide-trash-2" class="size-3" />
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-gray-300">
                            Nenhum membro neste workspace.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- Convites Pendentes --}}
    @if($pendingInvitations->isNotEmpty())
        <div>
            <h3 class="text-lg font-bold text-blue-dark mb-3">Convites pendentes</h3>

            <section class="border-dotted border border-gray-200 rounded-lg">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-4 text-start text-sm text-gray-300">E-mail</th>
                            <th class="px-4 py-4 text-start text-sm text-gray-300">Função</th>
                            <th class="px-4 py-4 text-start text-sm text-gray-300">Enviado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingInvitations as $invitation)
                            <tr class="border-t border-dotted border-gray-200">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center size-10 rounded-full bg-gray-100">
                                            <x-icon name="lucide-mail" class="size-4 text-gray-400" />
                                        </div>
                                        <span class="text-sm">{{ $invitation->email }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-700">
                                        Pendente - {{ ucfirst($invitation->role->value) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-sm text-gray-400">{{ $invitation->created_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
    @endif
</div>
