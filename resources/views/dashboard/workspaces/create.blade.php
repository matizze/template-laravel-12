<x-layout.dashboard>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Criar novo workspace
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mb-8">
                    Um workspace é um espaço colaborativo onde você pode convidar membros e gerenciar seus projetos.
                </p>

                <form method="POST" action="{{ route('workspace.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-form.input 
                            name="name" 
                            label="Nome do workspace" 
                            placeholder="Meu Workspace"
                            required
                            autofocus
                        />
                    </div>

                    <div>
                        <x-form.input 
                            name="slug" 
                            label="URL slug" 
                            placeholder="meu-workspace"
                            required
                            help="Identificador único para o workspace. Será usado na URL."
                        />
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descrição (opcional)
                        </label>
                        <textarea 
                            name="description" 
                            id="description" 
                            rows="4" 
                            placeholder="Descreva o propósito do seu workspace..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </textarea>
                    </div>

                    <div class="flex gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </a>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Criar workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layout.dashboard>
