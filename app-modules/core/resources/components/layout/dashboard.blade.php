@props(['title' => null])

<x-layout.app :title="$title">
    <div class="pt-3 h-full grid grid-cols-[200px_minmax(0,1fr)] overflow-visible">
        <aside class="flex flex-col h-full overflow-visible">
            <header class="flex px-5 py-6 space-x-3">
                <img
                    src="/brand/logo.svg"
                    alt="Dashboard"
                    class="size-11 select-none pointer-events-none"
                />
                <div>
                    <h1 class="text-lg font-bold text-gray-100">{{ config('app.name') }}</h1>
                    <p class="text-xss uppercase text-blue-light">{{ auth()->user()->role }}</p>
                </div>
            </header>

            <div class="px-4 py-2">
                <x-tenant::tenant-switcher :tenants="$tenants" :currentTenant="$currentTenant" :needsPath="$needsPath ?? false" />
            </div>

            <nav class="flex px-4 py-5 flex-col space-y-1">
                <x-nav-item icon="layout-dashboard" route="dashboard">
                    Dashboard
                </x-nav-item>

            </nav>

            <footer class="mt-auto px-4 py-5">
                <x-user-menu />
            </footer>
        </aside>

        <main {{ $attributes->class(["bg-white text-gray-600 rounded-tl-2xl px-12 py-13 overflow-y-auto"]) }}>
            {{ $slot }}
        </main>
    </div>
</x-layout.app>
