<x-layout.app>
    <x-slot:title>Redefinir senha</x-slot:title>

    <div class="relative h-full overflow-hidden">
      <img
        src="/brand/background.png"
        class="absolute inset-0 w-full h-full object-cover"
        alt="Fundo da marca"
      />

      <main class="absolute right-0 z-10 h-full">
          <div class="relative bg-white h-full w-screen rounded-t-2xl p-6 mt-8 sm:mt-4 sm:w-2xl sm:rounded-l-2xl sm:rounded-t-none sm:px-36 sm:py-12 space-y-8 justify-center overflow-y-auto">
            <img
                src="/brand/nav-header.svg"
                class="object-cover h-10 w-auto place-self-center"
            />

            <div class="space-y-3">
                <x-card class="flex-col space-y-10">
                    <span class="text-xs text-gray-400">
                        <h1 class="text-lg text-gray-500 font-bold">Redefinir senha</h1>
                        Informe sua nova senha
                    </span>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-10">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="flex flex-col space-y-4">
                            <x-form.input
                                label='E-mail'
                                name='email'
                                type='email'
                                placeholder='exemplo@mail.com'
                                autocomplete='email'
                                :value="request()->query('email')"
                                required
                            />

                            <x-form.input
                                label='Nova senha'
                                name='password'
                                type='password'
                                placeholder='Digite sua nova senha...'
                                autocomplete='new-password'
                                required
                            />

                            <x-form.input
                                label='Confirmar nova senha'
                                name='password_confirmation'
                                type='password'
                                placeholder='Confirme sua nova senha...'
                                autocomplete='new-password'
                                required
                            />
                        </div>

                        <x-button class="w-full" type='submit'>Redefinir senha</x-button>
                    </form>
                </x-card>
            </div>
        </div>
    </main>
</x-layout.app>
