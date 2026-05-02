<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompromissoResource\Pages;
use App\Models\Compromisso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompromissoResource extends Resource
{
    protected static ?string $model = Compromisso::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Agenda Pastoral';
    protected static ?string $modelLabel = 'Compromisso';
    protected static ?string $pluralModelLabel = 'Compromissos';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')->required()->columnSpanFull(),
                Forms\Components\DatePicker::make('data')->required()->displayFormat('d/m/Y')->minDate(now()->startOfDay()),
                Forms\Components\TimePicker::make('hora')->seconds(false),
                Forms\Components\Select::make('tipo')->required()->options([
                    'reuniao' => 'Reunião', 'formacao' => 'Formação', 'visita' => 'Visita',
                    'celebracao' => 'Celebração', 'evento' => 'Evento', 'outro' => 'Outro',
                ]),
                Forms\Components\TextInput::make('local'),
                Forms\Components\TextInput::make('responsavel')->label('Responsável'),
                Forms\Components\Toggle::make('publico')->label('Visível no site')->default(true),
                Forms\Components\Textarea::make('observacao')->label('Observação')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('titulo')->searchable()->limit(60),
            Tables\Columns\TextColumn::make('data')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('hora'),
            Tables\Columns\TextColumn::make('tipo')->badge()->color('info'),
            Tables\Columns\TextColumn::make('local')->limit(30),
            Tables\Columns\IconColumn::make('publico')->boolean()->label('Público'),
        ])
        ->defaultSort('data', 'asc')
        ->filters([
            Tables\Filters\SelectFilter::make('tipo')->options([
                'reuniao' => 'Reunião', 'formacao' => 'Formação', 'visita' => 'Visita',
                'celebracao' => 'Celebração', 'evento' => 'Evento', 'outro' => 'Outro',
            ]),
            Tables\Filters\TernaryFilter::make('publico')->label('Público'),
        ])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
        ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompromissos::route('/'),
            'create' => Pages\CreateCompromisso::route('/create'),
            'edit'   => Pages\EditCompromisso::route('/{record}/edit'),
        ];
    }
}
