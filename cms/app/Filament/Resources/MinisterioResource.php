<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\IconPickerField;
use App\Filament\Resources\MinisterioResource\Pages;
use App\Models\Ministerio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MinisterioResource extends Resource
{
    protected static ?string $model = Ministerio::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Ministérios';
    protected static ?string $modelLabel = 'Ministério';
    protected static ?string $pluralModelLabel = 'Ministérios';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('nome')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
                Forms\Components\Select::make('categoria')
                    ->label('Categoria')
                    ->options([
                        'ministerio'     => 'Ministério',
                        'catequese'      => 'Catequese',
                        'estudo-biblico' => 'Estudo Bíblico',
                        'grupo-oracao'   => 'Grupo de Oração',
                        'outro'          => 'Outro',
                    ])
                    ->default('ministerio')
                    ->required(),
                Forms\Components\Textarea::make('descricao')->label('Descrição')->required()->rows(3)->columnSpanFull(),
                IconPickerField::make('icone')->label('Ícone'),
                Forms\Components\FileUpload::make('imagem')
                    ->label('Imagem do Ministério')
                    ->disk('public')
                    ->directory('uploads/ministerios')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('4:3')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('600')
                    ->imageResizeMode('cover')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->hint('Ideal: 800 × 600 px (4:3) | JPG, PNG ou WebP | Máx. 2 MB')
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
                Forms\Components\Toggle::make('ativo')->label('Ativo')->default(true),
            ]),
            Forms\Components\Section::make('Coordenador')->columns(3)->schema([
                Forms\Components\TextInput::make('coordenador_nome')->label('Nome'),
                Forms\Components\TextInput::make('coordenador_telefone')->label('Telefone'),
                Forms\Components\TextInput::make('coordenador_email')->label('E-mail')->email(),
            ]),
            Forms\Components\Section::make('Encontros')->columns(3)->schema([
                Forms\Components\TextInput::make('encontros_dia_semana')->label('Dia da semana'),
                Forms\Components\TextInput::make('encontros_horario')->label('Horário'),
                Forms\Components\TextInput::make('encontros_local')->label('Local'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('coordenador_nome')->label('Coordenador')->searchable(),
            Tables\Columns\TextColumn::make('encontros_dia_semana')->label('Dia'),
            Tables\Columns\TextColumn::make('encontros_horario')->label('Horário'),
            Tables\Columns\IconColumn::make('ativo')->boolean(),
        ])
        ->filters([Tables\Filters\TernaryFilter::make('ativo')])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMinisterios::route('/'),
            'create' => Pages\CreateMinisterio::route('/create'),
            'edit'   => Pages\EditMinisterio::route('/{record}/edit'),
        ];
    }
}
