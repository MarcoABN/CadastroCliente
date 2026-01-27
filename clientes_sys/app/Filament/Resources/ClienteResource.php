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
                // Estilização para manter o "X" na direita e textos legíveis
                Placeholder::make('custom_layout_css')
                    ->label('')
                    ->content(new HtmlString('
                        <style>
                            .file-uploader-custom .filepond--item { width: 100%; }
                            .file-uploader-custom .filepond--file-action-button.filepond--action-remove-item {
                                right: 10px !important;
                                left: auto !important;
                                margin-left: auto;
                            }
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
                            ->displayFormat('d/m/Y')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $state ? $set('idade', Carbon::parse($state)->age) : null),
                        TextInput::make('idade')->numeric()->readOnly(),
                        TextInput::make('nome_pai')->label('Nome do Pai'),
                        TextInput::make('nome_mae')->label('Nome da Mãe'),
                        TextInput::make('telefone')->mask('(62) 9 9999-9999'),
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

                Section::make('Status e Bancário')
                    ->schema([
                        Toggle::make('correntista')->label('É Correntista?')->live(),
                        Toggle::make('simulacao')->label('Simulação Realizada'),
                        Toggle::make('proposta_enviada')->label('Proposta Enviada'),
                        Toggle::make('aprovada')->label('Aprovada')->onColor('success'),
                        
                        TextInput::make('banco')->visible(fn (Get $get) => $get('correntista')),
                        TextInput::make('agencia')->label('Agência')->visible(fn (Get $get) => $get('correntista')),
                        TextInput::make('conta_corrente')->label('Conta Corrente')->visible(fn (Get $get) => $get('correntista')),
                    ])->columns(4),

                Section::make('Arquivos')
                    ->schema([
                        FileUpload::make('documento_path')
                            ->label('Documentos PDF/Doc (Múltiplos)')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            // Removido o diretório para encontrar os arquivos na raiz da pasta public
                            ->directory('') 
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->extraAttributes(['class' => 'file-uploader-custom']),

                        FileUpload::make('foto_path')
                            ->label('Fotos (Múltiplas)')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            // Removido o diretório para encontrar os arquivos na raiz da pasta public
                            ->directory('')
                            ->visibility('public')
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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