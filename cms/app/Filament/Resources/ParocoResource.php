<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParocoResource\Pages;
use App\Models\Paroco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParocoResource extends Resource
{
    protected static ?string $model = Paroco::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Pároco';
    protected static ?string $modelLabel = 'Pároco';
    protected static ?string $pluralModelLabel = 'Párocos';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados pessoais')->columns(2)->schema([
                Forms\Components\TextInput::make('nome')->required(),
                Forms\Components\TextInput::make('saudacao')->label('Saudação (Pe., Mons.)')->default('Padre'),
                Forms\Components\DatePicker::make('data_ordenacao')->label('Data de ordenação')->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('data_inicio_paroquia')->label('Início na paróquia')->displayFormat('d/m/Y'),
                Forms\Components\FileUpload::make('foto')
                    ->label('Foto do Pároco')
                    ->disk('site_static')
                    ->directory('images/uploads/paroco')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('3:4')
                    ->imageResizeTargetWidth('600')
                    ->imageResizeTargetHeight('800')
                    ->imageResizeMode('cover')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->required()
                    ->hint('Ideal: 600 × 800 px (retrato 3:4) | JPG, PNG ou WebP | Máx. 2 MB')
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
            ]),
            Forms\Components\Section::make('Biografia')->schema([
                Forms\Components\RichEditor::make('biografia')->required()
                    ->toolbarButtons(['bold','italic','bulletList','orderedList','blockquote'])
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Contato')->columns(2)->schema([
                Forms\Components\TextInput::make('contato_email')->label('E-mail')->email(),
                Forms\Components\TextInput::make('contato_telefone')->label('Telefone'),
            ]),
            Forms\Components\Section::make('Redes sociais')->columns(2)->schema([
                Forms\Components\TextInput::make('redes_facebook')->label('Facebook (URL)')->url(),
                Forms\Components\TextInput::make('redes_instagram')->label('Instagram (URL)')->url(),
            ]),
            Forms\Components\Toggle::make('ativo')->label('Pároco atual')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('saudacao'),
            Tables\Columns\TextColumn::make('data_inicio_paroquia')->label('Desde')->date('d/m/Y'),
            Tables\Columns\IconColumn::make('ativo')->boolean()->label('Atual'),
        ])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListParocos::route('/'),
            'create' => Pages\CreateParoco::route('/create'),
            'edit'   => Pages\EditParoco::route('/{record}/edit'),
        ];
    }
}
