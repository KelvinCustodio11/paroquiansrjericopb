<?php

declare(strict_types=1);

namespace App\Filament\Resources\GaleriaAlbumResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FotosRelationManager extends RelationManager
{
    protected static string $relationship = 'fotos';
    protected static ?string $title = 'Fotos do Álbum';
    protected static ?string $modelLabel = 'Foto';
    protected static ?string $pluralModelLabel = 'Fotos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('arquivo')
                ->label('Arquivo')
                ->disk('site_static')
                ->directory('images/uploads/galeria')
                ->visibility('public')
                ->image()
                ->imageEditor()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->required()
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
            Forms\Components\TextInput::make('legenda')
                ->label('Legenda')
                ->maxLength(200),
            Forms\Components\TextInput::make('alt')
                ->label('Texto alternativo (acessibilidade)')
                ->maxLength(200),
            Forms\Components\TextInput::make('ordem')
                ->label('Ordem')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('ativa')
                ->label('Ativa')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->columns([
                Tables\Columns\ImageColumn::make('arquivo')
                    ->label('Imagem')
                    ->disk('site_static')
                    ->square()
                    ->width(80)
                    ->height(80),
                Tables\Columns\TextColumn::make('legenda')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('ordem')->sortable(),
                Tables\Columns\IconColumn::make('ativa')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload_multiplo')
                    ->label('Upload Múltiplo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('arquivos')
                            ->label('Selecione as imagens')
                            ->multiple()
                            ->disk('site_static')
                            ->directory('images/uploads/galeria')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $album = $this->getOwnerRecord();
                        $ordem = (int) ($album->fotos()->max('ordem') ?? -1);
                        foreach ($data['arquivos'] as $arquivo) {
                            $album->fotos()->create([
                                'arquivo' => ltrim((string) $arquivo, '/'),
                                'legenda' => '',
                                'alt'     => '',
                                'ordem'   => ++$ordem,
                                'ativa'   => true,
                            ]);
                        }
                    }),
                Tables\Actions\CreateAction::make()
                    ->label('Adicionar foto'),
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
}
