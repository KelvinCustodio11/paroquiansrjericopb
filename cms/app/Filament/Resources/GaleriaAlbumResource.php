<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GaleriaAlbumResource\Pages;
use App\Filament\Resources\GaleriaAlbumResource\RelationManagers;
use App\Models\GaleriaAlbum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GaleriaAlbumResource extends Resource
{
    protected static ?string $model = GaleriaAlbum::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Galeria';
    protected static ?string $modelLabel = 'Álbum';
    protected static ?string $pluralModelLabel = 'Álbuns';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título do álbum')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                        $set('slug', Str::slug($state)))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
                Forms\Components\Select::make('categoria')
                    ->label('Categoria / Pasta')
                    ->options([
                        'festividades'  => 'Festividades',
                        'celebracoes'   => 'Celebrações',
                        'eventos'       => 'Eventos',
                        'pastoral'      => 'Pastoral',
                        'obras'         => 'Obras',
                        'comunidade'    => 'Comunidade',
                        'outros'        => 'Outros',
                    ])
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('capa_imagem')
                    ->label('Imagem de capa')
                    ->disk('site_static')
                    ->directory('images/uploads/galeria')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                        if (is_string($state) && $state !== '') {
                            $component->state([ltrim($state, '/')]);
                        } elseif (! is_array($state)) {
                            $component->state([]);
                        }
                    })
                    ->dehydrateStateUsing(function ($state): ?string {
                        if (is_array($state)) {
                            $val = reset($state);
                            return $val !== false ? ltrim((string) $val, '/') : null;
                        }
                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ordem')
                    ->label('Ordem de exibição')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('publico')
                    ->label('Visível no site')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('capa_imagem')
                    ->label('Capa')
                    ->disk('site_static')
                    ->square()
                    ->width(64)
                    ->height(64),
                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('categoria')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('fotos_count')
                    ->label('Fotos')
                    ->counts('fotos')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('ordem')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('publico')
                    ->boolean()
                    ->label('Público'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->options([
                        'festividades' => 'Festividades',
                        'celebracoes'  => 'Celebrações',
                        'eventos'      => 'Eventos',
                        'pastoral'     => 'Pastoral',
                        'obras'        => 'Obras',
                        'comunidade'   => 'Comunidade',
                        'outros'       => 'Outros',
                    ]),
                Tables\Filters\TernaryFilter::make('publico')->label('Visibilidade'),
            ])
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\FotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGaleriaAlbuns::route('/'),
            'create' => Pages\CreateGaleriaAlbum::route('/create'),
            'edit'   => Pages\EditGaleriaAlbum::route('/{record}/edit'),
        ];
    }
}
