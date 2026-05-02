<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IgrejaResource\Pages;
use App\Models\Igreja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class IgrejaResource extends Resource
{
    protected static ?string $model = Igreja::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Igrejas / Horários';
    protected static ?string $modelLabel = 'Igreja';
    protected static ?string $pluralModelLabel = 'Igrejas';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados da Igreja')->columns(2)->schema([
                Forms\Components\TextInput::make('nome')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('tipo')->options([
                    'matriz'     => 'Matriz',
                    'capela'     => 'Capela',
                    'comunidade' => 'Comunidade',
                ])->required(),
                Forms\Components\Toggle::make('ativa')->label('Ativa')->default(true),
                Forms\Components\TextInput::make('endereco')->label('Endereço')->columnSpanFull(),
                Forms\Components\TextInput::make('bairro')->label('Bairro / Comunidade'),
            ]),
            Forms\Components\Section::make('Horários de Missa')->schema([
                Forms\Components\Repeater::make('horarios')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('dia_semana')->label('Dia')->required()->options([
                            'segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta',
                            'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo',
                        ]),
                        Forms\Components\TimePicker::make('hora')->required()->seconds(false),
                        Forms\Components\Select::make('tipo_celebracao')->label('Tipo')->options([
                            'missa' => 'Missa', 'novena' => 'Novena', 'adoracao' => 'Adoração',
                            'terco' => 'Terço', 'outro' => 'Outro',
                        ])->default('missa'),
                        Forms\Components\TextInput::make('observacao')->label('Observação'),
                    ])
                    ->columns(4)
                    ->addActionLabel('Adicionar horário')
                    ->reorderable(false),
            ]),
            Forms\Components\Section::make('História da Paróquia')
                ->description('Seções editáveis exibidas na página Sobre/História.')
                ->schema([
                    Forms\Components\TextInput::make('historia_titulo')
                        ->label('Título da seção de história')
                        ->maxLength(120),
                    Forms\Components\TextInput::make('historia_subtitulo')
                        ->label('Subtítulo')
                        ->maxLength(200),
                    Forms\Components\Repeater::make('historia_secoes')
                        ->label('Seções')
                        ->schema([
                            Forms\Components\TextInput::make('titulo')
                                ->label('Título da seção')
                                ->required()
                                ->maxLength(120),
                            Forms\Components\Textarea::make('texto')
                                ->label('Texto')
                                ->required()
                                ->rows(4),
                            Forms\Components\TextInput::make('imagem')
                                ->label('Caminho da imagem (ex: images/historia/foto.jpg)')
                                ->maxLength(300),
                        ])
                        ->columns(1)
                        ->addActionLabel('Adicionar seção')
                        ->collapsible()
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('tipo')->badge()->color('info'),
            Tables\Columns\TextColumn::make('bairro')->searchable(),
            Tables\Columns\IconColumn::make('ativa')->boolean(),
        ])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIgrejas::route('/'),
            'create' => Pages\CreateIgreja::route('/create'),
            'edit'   => Pages\EditIgreja::route('/{record}/edit'),
        ];
    }
}
