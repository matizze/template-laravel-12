<x-layout.dashboard title="Dashboard">
    <h1 class="text-2xl font-bold text-blue-dark">Dashboard</h1>

    <p class="text-gray-400">
        Bem-vindo, {{ auth()->user()->name ?? auth()->user()->email }}!
    </p>
</x-layout.dashboard>
