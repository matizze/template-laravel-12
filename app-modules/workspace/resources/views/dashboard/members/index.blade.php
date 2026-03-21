<x-layout.dashboard title="Membros">
    <div class="max-w-4xl">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Membros</h1>
                <p class="text-sm text-gray-500 mt-1">Gerencie os membros do workspace {{ $workspace->name }}</p>
            </div>

            <x-workspace::invite-modal :workspace="$workspace" />
        </div>

        {{-- Members list --}}
        <x-card>
            <div class="divide-y divide-gray-100">
                @foreach ($members as $member)
                    <div class="flex items-center justify-between py-4 px-2">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$member->user->name" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $member->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $member->user->email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($member->role->value === 'owner') bg-purple-100 text-purple-800
                                @elseif($member->role->value === 'admin') bg-blue-100 text-blue-800
                                @elseif($member->role->value === 'viewer') bg-gray-100 text-gray-600
                                @else bg-green-100 text-green-800
                                @endif
                            ">
                                {{ ucfirst($member->role->value) }}
                            </span>

                            @if($member->role->value !== 'owner')
                                @can('manageMembers', $workspace)
                                    <div class="flex items-center gap-1">
                                        <form method="POST" action="{{ route('workspace.members.updateRole', [$workspace, $member->user]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-form.select
                                                name="role"
                                                x-on:change="$el.form.submit()"
                                                aria-label="Alterar função do membro {{ $member->user->name }}"
                                                class="text-xs border border-gray-200 rounded-md px-2 py-1 !h-auto !border-b-0"
                                            >
                                                @foreach (\Modules\Workspace\Enums\WorkspaceRole::cases() as $role)
                                                    @if($role->value !== 'owner')
                                                        <option value="{{ $role->value }}" @selected($member->role === $role)>
                                                            {{ ucfirst($role->value) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </x-form.select>
                                        </form>

                                        <form method="POST" action="{{ route('workspace.members.remove', [$workspace, $member->user]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-button
                                                type="submit"
                                                variant="destructive-outline"
                                                class="!h-auto !p-1"
                                                title="Remover membro"
                                                aria-label="Remover membro"
                                                onclick="return confirm('Tem certeza que deseja remover este membro?')"
                                            >
                                                <x-icon name="lucide-trash-2" class="size-4" />
                                            </x-button>
                                        </form>
                                    </div>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Pending invitations --}}
        @if($pendingInvitations->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Convites pendentes</h2>
                <x-card>
                    <div class="divide-y divide-gray-100">
                        @foreach ($pendingInvitations as $invitation)
                            <div class="flex items-center justify-between py-4 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <x-icon name="lucide-mail" class="size-5 text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $invitation->email }}</p>
                                        <p class="text-xs text-gray-500">Convite enviado {{ $invitation->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>

                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pendente - {{ ucfirst($invitation->role->value) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        @endif
    </div>
</x-layout.dashboard>
