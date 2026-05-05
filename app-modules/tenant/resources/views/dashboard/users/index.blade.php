<x-layout.dashboard title="Usuários">
    <div class="max-w-4xl">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuários</h1>
                <p class="text-sm text-gray-500 mt-1">Gerencie os usuários do tenant {{ $tenant->name }}</p>
            </div>
        </div>

        @can('manageTenantUsers', $tenant)
            @if ($availableUsers->isNotEmpty())
                <x-card class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Vincular usuário</h2>
                    <form method="POST" action="{{ route('tenant.users.store', $tenant) }}" class="grid gap-4 md:grid-cols-3 md:items-end">
                        @csrf

                        <x-form.select name="user_id" label="Usuário" required>
                            <option value="">Selecione…</option>
                            @foreach ($availableUsers as $availableUser)
                                <option value="{{ $availableUser->id }}" @selected(old('user_id') == $availableUser->id)>
                                    {{ $availableUser->name }} ({{ $availableUser->email }})
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="role" label="Papel" required>
                            @foreach ([\Modules\Tenant\Enums\TenantRole::Admin, \Modules\Tenant\Enums\TenantRole::Member, \Modules\Tenant\Enums\TenantRole::Viewer] as $role)
                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ ucfirst($role->value) }}</option>
                            @endforeach
                        </x-form.select>

                        <div>
                            <x-button type="submit">Vincular</x-button>
                        </div>
                    </form>
                </x-card>
            @endif
        @endcan

        {{-- Tenant users list --}}
        <x-card>
            <div class="divide-y divide-gray-100">
                @foreach ($tenantUsers as $tenantUser)
                    <div class="flex items-center justify-between py-4 px-2">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$tenantUser->user->name" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $tenantUser->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $tenantUser->user->email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($tenantUser->role->value === 'owner') bg-purple-100 text-purple-800
                                @elseif($tenantUser->role->value === 'admin') bg-blue-100 text-blue-800
                                @elseif($tenantUser->role->value === 'viewer') bg-gray-100 text-gray-600
                                @else bg-green-100 text-green-800
                                @endif
                            ">
                                {{ ucfirst($tenantUser->role->value) }}
                            </span>

                            @if($tenantUser->role->value !== 'owner')
                                @can('manageTenantUsers', $tenant)
                                    <div class="flex items-center gap-1">
                                        <form method="POST" action="{{ route('tenant.users.updateRole', [$tenant, $tenantUser->user]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-form.select
                                                name="role"
                                                x-on:change="$el.form.submit()"
                                                aria-label="Alterar função do usuário {{ $tenantUser->user->name }}"
                                                class="text-xs border border-gray-200 rounded-md px-2 py-1 !h-auto !border-b-0"
                                            >
                                                @foreach (\Modules\Tenant\Enums\TenantRole::cases() as $role)
                                                    @if($role->value !== 'owner')
                                                        <option value="{{ $role->value }}" @selected($tenantUser->role === $role)>
                                                            {{ ucfirst($role->value) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </x-form.select>
                                        </form>

                                        <form method="POST" action="{{ route('tenant.users.remove', [$tenant, $tenantUser->user]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-button
                                                type="submit"
                                                variant="destructive-outline"
                                                class="!h-auto !p-1"
                                                title="Remover usuário"
                                                aria-label="Remover usuário"
                                                onclick="return confirm('Tem certeza que deseja remover este usuário?')"
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
    </div>
</x-layout.dashboard>
