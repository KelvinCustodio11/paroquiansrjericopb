<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomiliaResource\Pages;
use App\Models\Homilia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HomiliaResource extends Resource
{
    protected static ?string $model = Homilia::class;
    protected static ?string $navigationIcon = 'heroicon-o-microphone';
    protected static ?string $navigationLabel = 'Homilias';
    protected static ?string $modelLabel = 'Homilia';
    protected static ?string $pluralModelLabel = 'Homilias';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')->label('Título')->required()->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
            ]),
            Forms\Components\Section::make('Dados da celebração')->columns(2)->schema([
                Forms\Components\DatePicker::make('data')->required()->displayFormat('d/m/Y'),
                Forms\Components\TextInput::make('celebrante')->required(),
                Forms\Components\TextInput::make('ocasiao')->label('Ocasião')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Leitura do Evangelho')->columns(2)->schema([
                Forms\Components\TextInput::make('leitura_referencia')->label('Referência (ex.: Jo 20,1-9)'),
                Forms\Components\Textarea::make('leitura_texto')->label('Texto')->rows(3)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Conteúdo')->schema([
                Forms\Components\Textarea::make('resumo')->label('Resumo')->rows(3)->maxLength(320)->required()->columnSpanFull(),
                Forms\Components\RichEditor::make('transcricao')->label('Transcrição')
                    ->toolbarButtons(['bold','italic','bulletList','orderedList','blockquote'])
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Mídia')->columns(2)->schema([
                Forms\Components\TextInput::make('audio_url')->label('Áudio (URL)')->url(),
                Forms\Components\TextInput::make('video_url')->label('Vídeo YouTube (URL)')->url(),
                Forms\Components\FileUpload::make('imagem_capa_url')
                    ->label('Imagem de Capa')
                    ->disk('public')
                    ->directory('uploads/homilias')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('450')
                    ->imageResizeMode('cover')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->hint('Ideal: 800 × 450 px (16:9) | JPG, PNG ou WebP | Máx. 2 MB')
                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                        if (is_string($state) && $state !== '') {
                            $component->state([ltrim(preg_replace('#^storage/#', '', $state), '/')]);
                        } elseif (! is_array($state)) {
                            $component->state([]);
                        }
                    })
                    ->dehydrateStateUsing(function ($state): ?string {
                        if (is_array($state)) {
                            $val = reset($state);
                            return $val !== false ? 'storage/' . ltrim((string) $val, '/') : null;
                        }
                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('imagem_capa_alt')->label('Alt da imagem'),
            ]),
            Forms\Components\Toggle::make('publicado')->label('Publicado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('titulo')->searchable()->limit(60),
            Tables\Columns\TextColumn::make('celebrante')->searchable(),
            Tables\Columns\TextColumn::make('data')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('ocasiao')->label('Ocasião')->limit(40),
            Tables\Columns\IconColumn::make('publicado')->boolean(),
        ])
        ->defaultSort('data', 'desc')
        ->filters([Tables\Filters\TernaryFilter::make('publicado')])
        ->actions([
            Tables\Actions\Action::make('preview')
                ->label('Visualizar')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn ($record): string => 'http://localhost:3000/homilias/' . $record->slug . '.html')
                ->openUrlInNewTab(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHomilias::route('/'),
            'create' => Pages\CreateHomilia::route('/create'),
            'edit'   => Pages\EditHomilia::route('/{record}/edit'),
        ];
    }
}
