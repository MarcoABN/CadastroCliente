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
                // CSS para manter o botão X isolado na direita nos arquivos em linha
                Placeholder::make('custom_layout_css')
                    ->label('')
                    ->content(new HtmlString('
                        <style>
                            .file-uploader-custom .filepond--item { width: 100%; }
                            
                            /* Move o X para a extrema direita */
                            .file-uploader-custom .filepond--file-action-button.filepond--action-remove-item {
                                right: 10px !important;
                                left: auto !important;
                                margin-left: auto;
                            }

                            /* Ajusta informações do arquivo para não encavalar no X */
                            .file-uploader-custom .filepond--file-info {
                                margin-right: 80px !important;
                                opacity: 1 !important;
                                visibility: visible !important;
                                transform: none !important;
                            }

                            .file-uploader-custom .filepond--file-info-main {
                                color: white !important;
                                font-weight: bold;
                            }
                        </style>
                    '))
                    ->columnSpanFull(),

                Section::make('Dados Pessoais')
                    ->schema([
                        TextInput::make('nome')->required()->label('Nome Completo'),
                        TextInput::make('cpf')->mask('999.999.999-99')->required()->label('CPF'),
                        DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $state ? $set('idade', Carbon::parse($state)->age) : null),
                        TextInput::make('idade')->numeric()->readOnly(),
                        TextInput::make('nome_pai')->label('Nome do Pai'),
                        TextInput::make('nome_mae')->label('Nome da Mãe'),
                        TextInput::make('telefone')->mask('(99) 9 9999-9999'),
                        TextInput::make('email')->email(),
                    ])->columns(2),

                Section::make('Endereço')
                    ->schema([
                        TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) return;
                                $response = Http::get("https://viacep.com.br/ws/{$state}/json/")->json();
                                if (!isset($response['erro'])) {
                                    $set('endereco', $response['logradouro'] ?? '');
                                    $set('bairro', $response['bairro'] ?? '');
                                    $set('cidade', ($response['localidade'] ?? '') . '/' . ($response['uf'] ?? ''));
                                }
                            }),
                        TextInput::make('endereco')->label('Rua / Logradouro')->columnSpan(2),
                        TextInput::make('numero')->label('Número'),
                        TextInput::make('complemento')->label('Complemento'),
                        TextInput::make('bairro')->label('Bairro'),
                        TextInput::make('cidade')->label('Cidade/UF')->readOnly(),
                    ])->columns(3),

                Section::make('Dados Bancários')
                    ->schema([
                        Toggle::make('correntista')->label('É Correntista?')->live()->columnSpanFull(),
                        TextInput::make('banco')->visible(fn (Get $get) => $get('correntista')),
                        TextInput::make('agencia')->label('Agência')->visible(fn (Get $get) => $get('correntista')),
                        TextInput::make('conta_corrente')->label('Conta Corrente')->visible(fn (Get $get) => $get('correntista')),
                    ])->columns(3),

                Section::make('Status da Proposta')
                    ->schema([
                        Fieldset::make('Acompanhamento')
                            ->schema([
                                Toggle::make('simulacao')->label('Simulação Realizada'),
                                Toggle::make('proposta_enviada')->label('Proposta Enviada'),
                                Toggle::make('aprovada')->label('Aprovada')->onColor('success'),
                            ])->columns(3)
                    ]),

                Section::make('Observações')
                    ->schema([
                        Textarea::make('comentario_01')->label('Comentário 01')->rows(3)->columnSpanFull(),
                        Textarea::make('comentario_02')->label('Comentário 02')->rows(3)->columnSpanFull(),
                    ]),

                Section::make('Arquivos')
                    ->schema([
                        FileUpload::make('documento_path')
                            ->label('Documentos PDF/Doc (Múltiplos)')
                            ->multiple() 
                            ->reorderable()
                            ->appendFiles() // Mantém os que já existem ao subir novos
                            ->disk('public')
                            ->directory('clientes/documentos')
                            ->openable() // Abre em nova aba
                            ->downloadable() // Permite baixar
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->extraAttributes(['class' => 'file-uploader-custom']),
                            
                        FileUpload::make('foto_path')
                            ->label('Fotos (Múltiplas)')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('clientes/fotos')
                            ->imageEditor()
                            ->openable()
                            ->extraAttributes(['class' => 'file-uploader-custom']),
                    ])->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable(),
                Tables\Columns\TextColumn::make('cpf')->searchable(),
                Tables\Columns\TextColumn::make('cidade'),
            ])
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ]);
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