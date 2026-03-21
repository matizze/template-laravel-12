<x-layout.dashboard title="Configurações">
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <header class="shrink-0 pb-6">
            <h1 class="text-2xl font-bold text-blue-dark">
                Configurações
            </h1>
            <p class="text-sm text-gray-400 mt-1">
                Gerencie seu perfil e configurações da conta
            </p>
        </header>

        {{-- Layout Grid: Sidebar + Content --}}
        <div class="grid grid-cols-[250px_minmax(0,1fr)] gap-6 min-h-0 flex-1">
            {{-- Sidebar com Abas --}}
            <aside class="h-fit sticky top-0 space-y-2">
                <a
                    href="{{ route('settings.index', ['tab' => 'profile']) }}"
                    @class([
                        'flex items-center gap-3 px-4 py-3 text-sm transition-colors border-l-4',
                        'border-blue-base text-blue-dark font-semibold' => $active === 'profile',
                        'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-200' => $active !== 'profile',
                    ])
                >
                    <x-icon name="lucide-user" class="size-4" />
                    <span>Perfil</span>
                </a>

                <a
                    href="{{ route('settings.index', ['tab' => 'password']) }}"
                    @class([
                        'flex items-center gap-3 px-4 py-3 text-sm transition-colors border-l-4',
                        'border-blue-base text-blue-dark font-semibold' => $active === 'password',
                        'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-200' => $active !== 'password',
                    ])
                >
                    <x-icon name="lucide-lock" class="size-4" />
                    <span>Redefinir Senha</span>
                </a>

                @can('manage-users')
                    <a
                        href="{{ route('settings.index', ['tab' => 'users']) }}"
                        @class([
                            'flex items-center gap-3 px-4 py-3 text-sm transition-colors border-l-4',
                            'border-blue-base text-blue-dark font-semibold' => $active === 'users',
                            'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-200' => $active !== 'users',
                        ])
                    >
                        <x-icon name="lucide-shield" class="size-4" />
                        <span>Usuários</span>
                    </a>
                @endcan
            </aside>

            {{-- Conteúdo da Aba Ativa --}}
            <main class="overflow-y-auto">
                @switch($active)
                    @case('profile')
                        @include('user::settings.partials.profile')
                        @break
                    @case('password')
                        @include('user::settings.partials.password')
                        @break
                    @case('users')
                        @include('user::settings.partials.users')
                        @break
                @endswitch
            </main>
        </div>
    </div>
</x-layout.dashboard>
