<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\RadioBuscaExterna;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RadioBuscaExternaTabela extends BaseWidget
{
    // Não aparece no dashboard nem na descoberta automática do painel
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(RadioBuscaExterna::query())
            ->heading(null)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Regra')
                    ->model(RadioBuscaExterna::class)
                    ->form(self::formSchema()),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('label')
                    ->label('Regra')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('tag')
                    ->label('Tag')
                    ->default('—'),

                Tables\Columns\TextColumn::make('pais')
                    ->label('País')
                    ->default('BR'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->default('—'),

                Tables\Columns\TextColumn::make('limite')
                    ->label('Limite')
                    ->default('10'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(self::formSchema()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function formSchema(): array
    {
        return [
            Forms\Components\Section::make('Identificação')
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Nome da regra')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Ex: "Católicas da Paraíba", "Gospel do Brasil"')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Filtros da Radio Browser API')
                ->description('Configure quais rádios serão buscadas automaticamente. Todos os filtros são combinados (AND). Deixe em branco para não restringir.')
                ->schema([
                    Forms\Components\TextInput::make('tag')
                        ->label('Tag / Categoria')
                        ->maxLength(50)
                        ->helperText('Valores comuns: catholic, gospel, christian, religious, sertanejo...')
                        ->placeholder('catholic'),

                    Forms\Components\Select::make('pais')
                        ->label('País')
                        ->options([
                            'BR' => '🇧🇷 Brasil',
                            'PT' => '🇵🇹 Portugal',
                            'AO' => '🇦🇴 Angola',
                            'MZ' => '🇲🇿 Moçambique',
                        ])
                        ->default('BR')
                        ->required(),

                    Forms\Components\TextInput::make('estado')
                        ->label('Estado (nome completo)')
                        ->maxLength(80)
                        ->helperText('Nome completo como a API retorna: "Paraíba", "São Paulo"...')
                        ->placeholder('Paraíba'),

                    Forms\Components\TextInput::make('regiao')
                        ->label('Região (opcional)')
                        ->maxLength(80)
                        ->helperText('Ex: Nordeste, Sul, Sudeste — filtro adicional dentro do resultado')
                        ->placeholder('Nordeste'),

                    Forms\Components\TextInput::make('limite')
                        ->label('Máximo de rádios')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(50)
                        ->default(10)
                        ->helperText('Quantas rádios desta regra aparecem na lista (máx 50)'),

                    Forms\Components\TextInput::make('ordem')
                        ->label('Ordem de exibição')
                        ->numeric()
                        ->default(0),
                ])->columns(2),

            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('ativo')
                        ->label('Regra ativa')
                        ->default(true)
                        ->helperText('Desative para pausar a busca sem excluir a regra'),
                ]),
        ];
    }
}
