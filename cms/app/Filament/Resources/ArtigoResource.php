<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtigoResource\Pages;
use App\Models\Artigo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ArtigoResource extends Resource
{
    protected static ?string $model = Artigo::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Artigos';

    protected static ?string $modelLabel = 'Artigo';

    protected static ?string $pluralModelLabel = 'Artigos';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
                    ]),

                Forms\Components\Section::make('Publicação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('data_publicacao')
                            ->label('Data de publicação')
                            ->required()
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('data_atualizacao')
                            ->label('Data de atualização')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('categoria')
                            ->options([
                                'noticias' => 'Notícias', 'espiritualidade' => 'Espiritualidade',
                                'pastoral' => 'Pastoral', 'comunidade' => 'Comunidade',
                                'formacao' => 'Formação', 'evangelho' => 'Evangelho', 'outro' => 'Outro',
                            ])
                            ->required(),
                        Forms\Components\TagsInput::make('tags')->separator(','),
                    ]),

                Forms\Components\Section::make('Autor')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('autor_nome')->label('Nome')->required(),
                        Forms\Components\TextInput::make('autor_papel')->label('Papel / Função'),
                        Forms\Components\FileUpload::make('autor_foto')
                            ->label('Foto do Autor')
                            ->disk('site_static')
                            ->directory('images/uploads/autores')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->imageResizeMode('cover')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(1024)
                            ->hint('Ideal: 200 × 200 px (1:1) | Máx. 1 MB')
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
                            }),
                    ]),

                Forms\Components\Section::make('Conteúdo')
                    ->schema([
                        Forms\Components\Textarea::make('resumo')
                            ->rows(3)->maxLength(320)->required()->columnSpanFull(),
                        Forms\Components\RichEditor::make('conteudo')
                            ->required()
                            ->toolbarButtons(['bold','italic','bulletList','orderedList','link','h2','h3','blockquote'])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Imagem de capa')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('imagem_capa_url')
                            ->label('Imagem de Capa')
                            ->disk('site_static')
                            ->directory('images/uploads/artigos')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('628')
                            ->imageResizeMode('cover')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(3072)
                            ->required()
                            ->hint('Ideal: 1200 × 628 px (16:9) | JPG, PNG ou WebP | Máx. 3 MB')
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
                        Forms\Components\TextInput::make('imagem_capa_alt')->label('Texto alternativo (alt)')->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Visibilidade')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('publicado')->label('Publicado'),
                        Forms\Components\Toggle::make('destaque')->label('Destaque na home'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('autor_nome')->label('Autor')->searchable(),
                Tables\Columns\TextColumn::make('data_publicacao')->label('Publicado em')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('categoria')->badge()->color('info'),
                Tables\Columns\IconColumn::make('publicado')->boolean(),
                Tables\Columns\IconColumn::make('destaque')->boolean(),
            ])
            ->defaultSort('data_publicacao', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->options([
                        'noticias' => 'Notícias', 'espiritualidade' => 'Espiritualidade',
                        'pastoral' => 'Pastoral', 'comunidade' => 'Comunidade',
                        'formacao' => 'Formação', 'evangelho' => 'Evangelho', 'outro' => 'Outro',
                    ]),
                Tables\Filters\TernaryFilter::make('publicado'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArtigos::route('/'),
            'create' => Pages\CreateArtigo::route('/create'),
            'edit'   => Pages\EditArtigo::route('/{record}/edit'),
        ];
    }
}

