<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = 'Menu';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Itens de Menu';

    protected static ?string $modelLabel = 'Item de Menu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('titulo')
                ->label('Título')
                ->required()
                ->maxLength(80),

            Forms\Components\TextInput::make('link')
                ->label('Link (href)')
                ->required()
                ->maxLength(300)
                ->default('#')
                ->helperText('Ex: eventos.html, #ancora, https://...'),

            Forms\Components\TextInput::make('page_key')
                ->label('Page key (data-page)')
                ->maxLength(40)
                ->helperText('Valor do atributo data-page para marcar o item ativo. Ex: home, historia, eventos'),

            Forms\Components\Select::make('pai_id')
                ->label('Item pai (dropdown)')
                ->relationship('pai', 'titulo')
                ->nullable()
                ->helperText('Deixe vazio para item de nível raiz'),

            Forms\Components\TextInput::make('ordem')
                ->label('Ordem')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('visivel')
                ->label('Visível no menu')
                ->default(true),

            Forms\Components\Toggle::make('externo')
                ->label('Abrir em nova aba')
                ->default(false),

            Forms\Components\TextInput::make('icone')
                ->label('Ícone (opcional)')
                ->maxLength(80)
                ->helperText('Classe CSS do ícone, ex: fa-solid fa-home'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable(),

                Tables\Columns\TextColumn::make('link')
                    ->label('Link')
                    ->limit(50),

                Tables\Columns\TextColumn::make('page_key')
                    ->label('page_key')
                    ->badge(),

                Tables\Columns\TextColumn::make('pai.titulo')
                    ->label('Pai')
                    ->default('—'),

                Tables\Columns\IconColumn::make('visivel')
                    ->label('Visível')
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
            'index'  => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit'   => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
