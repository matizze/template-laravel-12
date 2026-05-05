<x-layout.dashboard title="Configurações do Tenant">
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <header class="shrink-0 pb-6">
            <h1 class="text-2xl font-bold text-blue-dark">
                Configurações do Tenant
            </h1>
            <p class="text-sm text-gray-400 mt-1">
                Gerencie as configurações e usuários do tenant <strong>{{ $tenant->name }}</strong>
            </p>
        </header>

        {{-- Layout Grid: Sidebar + Content --}}
        <div class="grid grid-cols-[250px_minmax(0,1fr)] gap-6 min-h-0 flex-1">
            {{-- Sidebar com Abas --}}
            <aside class="h-fit sticky top-0 space-y-2">
                <a
                    href="{{ route('tenant.settings.show', ['tenant' => $tenant, 'tab' => 'geral']) }}"
                    @class([
                        'flex items-center gap-3 px-4 py-3 text-sm transition-colors border-l-4',
                        'border-blue-base text-blue-dark font-semibold' => $active === 'geral',
                        'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-200' => $active !== 'geral',
                    ])
                >
                    <x-icon name="lucide-settings" class="size-4" />
                    <span>Geral</span>
                </a>

                <a
                    href="{{ route('tenant.settings.show', ['tenant' => $tenant, 'tab' => 'membros']) }}"
                    @class([
                        'flex items-center gap-3 px-4 py-3 text-sm transition-colors border-l-4',
                        'border-blue-base text-blue-dark font-semibold' => $active === 'membros',
                        'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-200' => $active !== 'membros',
                    ])
                >
                    <x-icon name="lucide-users" class="size-4" />
                    <span>Membros</span>
                </a>
            </aside>

            {{-- Conteúdo da Aba Ativa --}}
            <main class="overflow-y-auto">
                @switch($active)
                    @case('geral')
                        @include('tenant::dashboard.tenant-settings.partials.geral', ['tenant' => $tenant])
                        @break
                    @case('membros')
                        @include('tenant::dashboard.tenant-settings.partials.membros', ['tenant' => $tenant, 'tenantUsers' => $tenantUsers])
                        @break
                @endswitch
            </main>
        </div>
    </div>
</x-layout.dashboard>
