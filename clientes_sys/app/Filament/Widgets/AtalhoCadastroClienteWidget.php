<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AtalhoCadastroClienteWidget extends Widget
{
    protected static string $view = 'filament.widgets.atalho-cadastro-cliente-widget';

    // Isso faz o widget ocupar a largura total da tela (opcional, remova se quiser pequeno)
    protected int | string | array $columnSpan = 'full'; 
}