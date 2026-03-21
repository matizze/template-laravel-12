<x-layout.dashboard>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Membros do workspace
            </h1>
            @can('invite', $workspace)
                <button 
                    dusk="btn-invite-member"
                    @click="window.dispatchEvent(new CustomEvent('open-invite-modal'))" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <x-lucide-plus class="w-4 h-4" />
                    Convidar membro
                </button>
            @endcan
        </div>

        @if ($invitations->count() > 0)
            <div dusk="pending-invitations-list" class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Convites pendentes
                    </h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($invitations as $invitation)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $invitation->email }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Função: {{ $invitation->role->label() }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/20 rounded">
                                Pendente
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Membros ativos ({{ $members->total() }})
                </h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($members as $member)
                    <div dusk="member-{{ $member->id }}" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <x-avatar name="{{ $member->user->name }}" />
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $member->user->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $member->user->email }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @can('updateMemberRole', $workspace)
                                @if (!$member->isOwner())
                                    <form method="POST" action="{{ route('members.updateRole', $member) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <select 
                                            dusk="select-member-role-{{ $member->id }}"
                                            name="role" 
                                            onchange="this.form.submit()"
                                            class="text-sm px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                            @foreach (\App\Enums\WorkspaceRole::assignable() as $role)
                                                <option value="{{ $role->value }}" {{ $member->role === $role ? 'selected' : '' }}>
                                                    {{ $role->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/20 rounded">
                                        {{ $member->role->label() }}
                                    </span>
                                @endif
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900/20 rounded">
                                    {{ $member->role->label() }}
                                </span>
                            @endcan

                            @can('removeMembers', $workspace)
                                @if (!$member->isOwner())
                                    <form method="POST" action="{{ route('members.remove', $member) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button dusk="btn-remove-member-{{ $member->id }}" type="submit" onclick="return confirm('Tem certeza?')" class="text-red-600 dark:text-red-400 hover:text-red-700">
                                            <x-lucide-trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        Nenhum membro adicionado ainda
                    </div>
                @endforelse
            </div>
        </div>

        @if ($members->hasPages())
            <div class="mt-6">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    <x-invite-modal />
</x-layout.dashboard>
