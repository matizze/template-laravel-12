<div class="space-y-8 w-full">
    <x-card class="w-full lg:w-2/3">
        <div class="space-y-6 w-full">
            <div>
                <h2 class="text-xl font-bold text-blue-dark">Membros</h2>
                <p class="text-sm text-gray-400 mt-1">Gerencie os membros deste workspace</p>
            </div>

            <div class="space-y-3">
                @foreach ($members as $membership)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$membership->user->name" size="sm" />
                            <div>
                                <p class="text-sm font-medium text-gray-600">{{ $membership->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $membership->user->email }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-500 capitalize">
                            {{ $membership->role->value }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </x-card>
</div>
