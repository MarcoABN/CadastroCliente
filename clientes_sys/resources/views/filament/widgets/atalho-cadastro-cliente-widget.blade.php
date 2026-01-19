<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            {{-- Ícone e Texto --}}
            <div class="flex-1">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                    Acesso Rápido
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Inicie o cadastro de um novo cliente agora mesmo.
                </p>
            </div>

            {{-- O Botão --}}
            <x-filament::button
                href="{{ \App\Filament\Resources\ClienteResource::getUrl('create') }}"
                tag="a"
                icon="heroicon-m-user-plus"
                color="primary"
            >
                Cadastrar Novo Cliente
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>