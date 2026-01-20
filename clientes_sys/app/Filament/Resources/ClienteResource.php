<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO DADOS PESSOAIS ---
                Section::make('Dados Pessoais')
                    ->schema([
                        TextInput::make('nome')
                            ->required()
                            ->label('Nome Completo'),
                        
                        TextInput::make('cpf')
                            ->mask('999.999.999-99')
                            ->required()
                            ->label('CPF'),
                        
                        DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento') // Alterado label
                            ->format('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->live(onBlur: true) // Alterado: Só dispara o cálculo ao sair do campo
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $set('idade', Carbon::parse($state)->age);
                                }
                            }),

                        TextInput::make('idade')
                            ->numeric()
                            ->readOnly(), // Boa prática: deixar idade como leitura, já que é calculado
                            
                        TextInput::make('nome_pai')
                            ->label('Nome do Pai'), // Alterado label
                            
                        TextInput::make('nome_mae')
                            ->label('Nome da Mãe'), // Alterado label
                            
                        TextInput::make('telefone')
                            ->mask('(99) 9 9999-9999'),
                            
                        TextInput::make('email')
                            ->email(),
                    ])->columns(2),

                // --- SEÇÃO ENDEREÇO ---
                Section::make('Endereço')
                    ->schema([
                        TextInput::make('cep')
                        ->label('CEP')
                        ->mask('99999-999')
                        ->live(onBlur: true)
                        // --- CÓDIGO DO LOADING COMEÇA AQUI ---
                        ->helperText(new HtmlString('
                            <div wire:loading wire:target="data.cep" class="text-primary-500 text-sm font-bold flex items-center gap-2 mt-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Buscando endereço...
                            </div>
                        '))
                        // --- CÓDIGO DO LOADING TERMINA AQUI ---
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (!$state) return;
                            
                            $response = Http::get("https://viacep.com.br/ws/{$state}/json/")->json();
                            
                            if (!isset($response['erro'])) {
                                $set('endereco', $response['logradouro'] ?? '');
                                $set('bairro', $response['bairro'] ?? '');
                                $set('cidade', ($response['localidade'] ?? '') . '/' . ($response['uf'] ?? ''));
                            }
                        }),

                        TextInput::make('endereco')
                            ->label('Rua / Logradouro')
                            ->columnSpan(2), // Ocupa mais espaço visualmente
                            
                        TextInput::make('numero')
                            ->label('Número')
                            ->numeric()
                            ->integer(),

                        TextInput::make('complemento')
                            ->label('Complemento'),
                            
                        TextInput::make('bairro')
                            ->label('Bairro'),
                            
                        TextInput::make('cidade')
                            ->label('Cidade/UF')
                            ->readOnly(), // Geralmente vem do CEP, melhor travar ou deixar livre conforme regra
                    ])->columns(3), // Grid de 3 colunas para melhor organização

                // --- SEÇÃO BANCÁRIA ---
                Section::make('Dados Bancários')
                    ->schema([
                        Toggle::make('correntista')
                            ->label('É Correntista?')
                            ->live() // Necessário para atualizar a tela e mostrar os campos abaixo
                            ->columnSpanFull(),

                        // Grupo que só aparece se for correntista
                        TextInput::make('banco')
                            ->visible(fn (Get $get) => $get('correntista')),
                            
                        TextInput::make('agencia')
                            ->label('Agência')
                            ->visible(fn (Get $get) => $get('correntista')),
                            
                        TextInput::make('conta_corrente')
                            ->label('Conta Corrente')
                            ->visible(fn (Get $get) => $get('correntista')),
                    ])->columns(3),

                // --- SEÇÃO STATUS ---
                Section::make('Status da Proposta')
                    ->schema([
                        Fieldset::make('Acompanhamento')
                            ->schema([
                                Toggle::make('simulacao')
                                    ->label('Simulação Realizada')
                                    ->inline(false), // Opcional: deixa o label em cima do toggle
                                    
                                Toggle::make('proposta_enviada')
                                    ->label('Proposta Enviada')
                                    ->inline(false),
                                    
                                Toggle::make('aprovada')
                                    ->label('Aprovada')
                                    ->inline(false)
                                    ->onColor('success'), // Verde para aprovado
                            ])->columns(3)
                    ]),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('comentario_01')
                            ->label('Comentário 01')
                            ->rows(3) // Altura inicial do campo
                            ->maxLength(500) // Limite de caracteres no front-end
                            ->columnSpanFull(), // Ocupa a largura total do formulário

                        Textarea::make('comentario_02')
                            ->label('Comentário 02')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                // --- SEÇÃO ARQUIVOS (Mantida conforme anterior) ---
                Section::make('Arquivos')
                    ->schema([
                        FileUpload::make('documento_path')
                            ->label('Documento PDF/Doc')
                            ->disk('public')
                            ->openable()
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                            
                        FileUpload::make('foto_path')
                            ->image()
                            ->label('Foto')
                            ->openable(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable(),
                Tables\Columns\TextColumn::make('cpf')->searchable()
                ->formatStateUsing(function ($state) {
                    // Lógica: Pega os primeiros 4 caracteres (000.), adiciona o meio oculto, e pega os 2 últimos
                    // Assume que o CPF já está salvo com pontuação (ex: 036.746.601-56)
                    if (!$state) return null;
                    return substr($state, 0, 4) . 'xxx.xxx-' . substr($state, -2);
                }),
                Tables\Columns\TextColumn::make('cidade'),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ]);
            
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}