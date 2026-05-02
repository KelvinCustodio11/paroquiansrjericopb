<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RadioResource\Pages;
use App\Models\Radio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RadioResource extends Resource
{
    protected static ?string $model = Radio::class;

    protected static ?string $navigationIcon = 'heroicon-o-radio';

    protected static ?string $navigationLabel = 'Rádios';

    protected static ?string $navigationGroup = 'Conteúdo';

    protected static ?int $navigationSort = 10;

    protected static ?string $pluralModelLabel = 'Rádios';

    protected static ?string $modelLabel = 'Rádio';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nome')
                ->label('Nome da Rádio')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('url')
                ->label('URL do Stream')
                ->required()
                ->url()
                ->maxLength(500)
                ->helperText('URL direta do stream de áudio (mp3, aac, ogg...)'),

            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
                ->maxLength(200),

            Forms\Components\TextInput::make('favicon')
                ->label('URL do Favicon/Logo (opcional)')
                ->url()
                ->maxLength(500),

            Forms\Components\Toggle::make('destaque')
                ->label('Rádio em destaque (aparece no topo)')
                ->default(false),

            Forms\Components\Toggle::make('ativa')
                ->label('Ativa')
                ->default(true),

            Forms\Components\TextInput::make('ordem')
                ->label('Ordem de exibição')
                ->numeric()
                ->default(0),

            Forms\Components\Select::make('categoria')
                ->label('Categoria')
                ->options([
                    'catolica'  => 'Católica',
                    'gospel'    => 'Gospel',
                    'religiosa' => 'Religiosa',
                    'regional'  => 'Regional',
                    'outra'     => 'Outra',
                ])
                ->default('catolica')
                ->required(),

            Forms\Components\Select::make('estado')
                ->label('Estado (UF)')
                ->options([
                    'AC' => 'AC', 'AL' => 'AL', 'AM' => 'AM', 'AP' => 'AP', 'BA' => 'BA',
                    'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
                    'MG' => 'MG', 'MS' => 'MS', 'MT' => 'MT', 'PA' => 'PA', 'PB' => 'PB',
                    'PE' => 'PE', 'PI' => 'PI', 'PR' => 'PR', 'RJ' => 'RJ', 'RN' => 'RN',
                    'RO' => 'RO', 'RR' => 'RR', 'RS' => 'RS', 'SC' => 'SC', 'SE' => 'SE',
                    'SP' => 'SP', 'TO' => 'TO',
                ])
                ->searchable()
                ->nullable(),

            Forms\Components\TextInput::make('cidade')
                ->label('Cidade de origem')
                ->maxLength(80)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Stream')
                    ->limit(60),

                Tables\Columns\IconColumn::make('destaque')
                    ->label('Destaque')
                    ->boolean(),

                Tables\Columns\BadgeColumn::make('categoria')
                    ->label('Categoria'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->default('—'),

                Tables\Columns\IconColumn::make('ativa')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRadios::route('/'),
            'create' => Pages\CreateRadio::route('/create'),
            'edit'   => Pages\EditRadio::route('/{record}/edit'),
        ];
    }
}
