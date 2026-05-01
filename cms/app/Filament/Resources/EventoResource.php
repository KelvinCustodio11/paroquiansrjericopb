<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventoResource\Pages;
use App\Models\Evento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventoResource extends Resource
{
    protected static ?string $model = Evento::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Eventos';

    protected static ?string $modelLabel = 'Evento';

    protected static ?string $pluralModelLabel = 'Eventos';

    protected static ?int $navigationSort = 1;

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
                        Forms\Components\TextInput::make('subtitulo')
                            ->label('Subtítulo')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data e Local')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data de Início')
                            ->required()
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data de Término')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\TimePicker::make('hora_inicio')
                            ->label('Horário')
                            ->seconds(false),
                        Forms\Components\TextInput::make('local')
                            ->label('Local')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Classificação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('categoria')
                            ->required()
                            ->options([
                                'liturgico'  => 'Litúrgico',
                                'pastoral'   => 'Pastoral',
                                'social'     => 'Social',
                                'formativo'  => 'Formativo',
                                'festivo'    => 'Festivo',
                                'outro'      => 'Outro',
                            ]),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->default('agendado')
                            ->options([
                                'agendado'      => 'Agendado',
                                'em-andamento'  => 'Em andamento',
                                'encerrado'     => 'Encerrado',
                                'cancelado'     => 'Cancelado',
                            ]),
                    ]),

                Forms\Components\Section::make('Conteúdo')
                    ->schema([
                        Forms\Components\Textarea::make('resumo')
                            ->label('Resumo')
                            ->rows(3)
                            ->maxLength(320)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('conteudo')
                            ->label('Conteúdo completo')
                            ->toolbarButtons(['bold','italic','bulletList','orderedList','link','h2','h3','blockquote'])
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('imagem_capa')
                            ->label('Imagem de Capa')
                            ->disk('site_static')
                            ->directory('images/uploads/events')
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
                                // FileUpload trabalha internamente com array; string vinda do banco precisa ser envolvida
                                if (is_string($state) && $state !== '') {
                                    $component->state([ltrim($state, '/')]);
                                } elseif (! is_array($state)) {
                                    $component->state([]);
                                }
                            })
                            ->dehydrateStateUsing(function ($state): ?string {
                                // Salva como string simples no banco
                                if (is_array($state)) {
                                    $val = reset($state);
                                    return $val !== false ? ltrim((string) $val, '/') : null;
                                }
                                return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Publicação')
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
                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('categoria')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'liturgico' => 'info',
                        'festivo'   => 'warning',
                        'social'    => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agendado'     => 'info',
                        'em-andamento' => 'warning',
                        'encerrado'    => 'gray',
                        'cancelado'    => 'danger',
                        default        => 'gray',
                    }),
                Tables\Columns\IconColumn::make('publicado')->boolean(),
                Tables\Columns\IconColumn::make('destaque')->boolean(),
            ])
            ->defaultSort('data_inicio', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->options([
                        'liturgico' => 'Litúrgico', 'pastoral' => 'Pastoral',
                        'social' => 'Social', 'formativo' => 'Formativo',
                        'festivo' => 'Festivo', 'outro' => 'Outro',
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventos::route('/'),
            'create' => Pages\CreateEvento::route('/create'),
            'edit' => Pages\EditEvento::route('/{record}/edit'),
        ];
    }
}
