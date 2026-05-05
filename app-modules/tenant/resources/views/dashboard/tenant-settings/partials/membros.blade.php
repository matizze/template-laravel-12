<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-blue-dark">Membros</h2>
            <p class="text-sm text-gray-400 mt-1">Gerencie os membros deste tenant</p>
        </div>
    </div>

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
                @forelse ($tenantUsers as $tenantUser)
                    <tr class="border-t border-dotted border-gray-200">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$tenantUser->user->name" />
                                <span class="text-sm">{{ $tenantUser->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm text-gray-500">{{ $tenantUser->user->email }}</span>
                        </td>
                        <td class="px-4 py-4">
                            <span @class([
                                'inline-flex items-center px-2 py-1 text-xs font-medium rounded',
                                'bg-indigo-100 text-indigo-700' => $tenantUser->role === \Modules\Tenant\Enums\TenantRole::Owner,
                                'bg-blue-100 text-blue-700' => $tenantUser->role === \Modules\Tenant\Enums\TenantRole::Admin,
                                'bg-gray-100 text-gray-700' => $tenantUser->role === \Modules\Tenant\Enums\TenantRole::Member,
                                'bg-gray-50 text-gray-500' => $tenantUser->role === \Modules\Tenant\Enums\TenantRole::Viewer,
                            ])>
                                {{ ucfirst($tenantUser->role->value) }}
                            </span>
                        </td>
                        <td class="flex gap-2 px-4 py-4">
                            @can('manageTenantUsers', $tenant)
                                @if($tenantUser->role !== \Modules\Tenant\Enums\TenantRole::Owner)
                                    <form method="POST" action="{{ route('tenant.users.updateRole', [$tenant, $tenantUser->user]) }}">
                                        @method('PATCH')
                                        @csrf
                                        <x-form.select
                                            name="role"
                                            aria-label="Alterar função de {{ $tenantUser->user->name }}"
                                            x-on:change="$el.form.submit()"
                                        >
                                            @foreach (\Modules\Tenant\Enums\TenantRole::cases() as $role)
                                                @if($role !== \Modules\Tenant\Enums\TenantRole::Owner)
                                                    <option value="{{ $role->value }}" {{ $tenantUser->role === $role ? 'selected' : '' }}>
                                                        {{ ucfirst($role->value) }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </x-form.select>
                                    </form>

                                    <div x-data="{
                                        confirmRemove() {
                                            return confirm('Tem certeza que deseja remover este membro?');
                                        }
                                    }">
                                        <form
                                            method="POST"
                                            action="{{ route('tenant.users.remove', [$tenant, $tenantUser->user]) }}"
                                            @submit="if (!confirmRemove()) $event.preventDefault()"
                                        >
                                            @method('DELETE')
                                            @csrf
                                            <button
                                                type="submit"
                                                class="flex items-center justify-center cursor-pointer size-7 bg-red-100 text-red-600 rounded"
                                                title="Remover membro"
                                                aria-label="Remover {{ $tenantUser->user->name }}"
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
                            Nenhum membro neste tenant.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
