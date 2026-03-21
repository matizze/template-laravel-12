@php
    $workspace = \App\Models\Workspace::current();
@endphp

<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Workspace</h2>

    <!-- Workspace Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações do workspace</h3>
        
        <form method="POST" action="{{ route('workspace.update', $workspace) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div>
                <x-form.input 
                    name="name" 
                    label="Nome" 
                    value="{{ $workspace->name }}"
                    required
                />
            </div>

            <div>
                <x-form.input 
                    name="slug" 
                    label="URL slug" 
                    value="{{ $workspace->slug }}"
                    required
                />
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Descrição
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ $workspace->description }}</textarea>
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Salvar alterações
            </button>
        </form>
    </div>

    <!-- Danger Zone -->
    @can('delete', $workspace)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Zona de perigo</h3>
            <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                A exclusão do workspace é permanente e não pode ser desfeita.
            </p>

            <form method="POST" action="{{ route('workspace.destroy', $workspace) }}" onsubmit="return confirm('Tem certeza que deseja deletar este workspace?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm" value="on">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Deletar workspace
                </button>
            </form>
        </div>
    @endcan
</div>
