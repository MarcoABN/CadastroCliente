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
use Filament\Forms\Components\Placeholder;
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
                // Estilização CSS para mover o botão 'X' do PDF para a direita
                

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
                            ->label('Data de Nascimento')
                            ->format('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $set('idade', Carbon::parse($state)->age);
                                }
                            }),

                        TextInput::make('idade')
                            ->numeric()
                            ->readOnly(),
                            
                        TextInput::make('nome_pai')
                            ->label('Nome do Pai'),
                            
                        TextInput::make('nome_mae')
                            ->label('Nome da Mãe'),
                            
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
                        ->helperText(new HtmlString('
                            <div wire:loading wire:target="data.cep" class="text-primary-500 text-sm font-bold flex items-center gap-2 mt-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Buscando endereço...
                            </div>
                        '))
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
                            ->columnSpan(2),
                            
                        TextInput::make('numero')
                            ->label('Número')
                            ->numeric(),

                        TextInput::make('complemento')
                            ->label('Complemento'),
                            
                        TextInput::make('bairro')
                            ->label('Bairro'),
                            
                        TextInput::make('cidade')
                            ->label('Cidade/UF')
                            ->readOnly(),
                    ])->columns(3),

                // --- SEÇÃO BANCÁRIA ---
                Section::make('Dados Bancários')
                    ->schema([
                        Toggle::make('correntista')
                            ->label('É Correntista?')
                            ->live()
                            ->columnSpanFull(),

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
                                    ->inline(false),
                                    
                                Toggle::make('proposta_enviada')
                                    ->label('Proposta Enviada')
                                    ->inline(false),
                                    
                                Toggle::make('aprovada')
                                    ->label('Aprovada')
                                    ->inline(false)
                                    ->onColor('success'),
                            ])->columns(3)
                    ]),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('comentario_01')
                            ->label('Comentário 01')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Textarea::make('comentario_02')
                            ->label('Comentário 02')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                // --- SEÇÃO ARQUIVOS ATUALIZADA ---
                Section::make('Arquivos')
                    ->schema([
                        FileUpload::make('documento_path')
                            ->label('Documentos PDF/Doc (Múltiplos)')
                            ->multiple() // Habilita múltiplos
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('clientes/documentos')
                            ->openable()
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->extraAttributes(['class' => 'pdf-uploader-custom']), // Classe para o CSS acima
                            
                        FileUpload::make('foto_path')
                            ->label('Fotos (Múltiplas)')
                            ->image()
                            ->multiple() // Habilita múltiplas
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('clientes/fotos')
                            ->imageEditor()
                            ->openable(),
                    ])->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable(),
                Tables\Columns\TextColumn::make('cpf')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 4) . 'xxx.xxx-' . substr($state, -2) : null),
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

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}