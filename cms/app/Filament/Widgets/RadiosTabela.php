<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Radio;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RadiosTabela extends BaseWidget
{
    // Não aparece no dashboard nem na descoberta automática do painel
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Radio::query())
            ->heading(null)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Rádio')
                    ->model(Radio::class)
                    ->form(self::formSchema()),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Stream')
                    ->limit(50),

                Tables\Columns\IconColumn::make('destaque')
                    ->label('Destaque')
                    ->boolean(),

                Tables\Columns\BadgeColumn::make('categoria')
                    ->label('Categoria'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->default('—'),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Transmissão')
                    ->formatStateUsing(fn ($record) => $record->hora_inicio
                        ? substr($record->hora_inicio, 0, 5) . ' – ' . substr($record->hora_fim ?? '', 0, 5)
                        : '—')
                    ->default('—'),

                Tables\Columns\IconColumn::make('ativa')
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
            Forms\Components\TextInput::make('nome')
                ->label('Nome da Rádio')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('url')
                ->label('URL do Stream')
                ->required()
                ->url()
                ->maxLength(500)
                ->helperText('URL direta do stream de áudio (mp3, aac, ogg...)'),

            Forms\Components\TextInput::make('descricao')
                ->label('Descrição curta')
                ->maxLength(200)
                ->helperText('Linha 1 sob o nome da rádio no player (ex: "Rádio diocesana — João Pessoa/PB")'),

            Forms\Components\TextInput::make('programacao')
                ->label('Programação / Horários')
                ->maxLength(200)
                ->helperText('Linha 2 no player: programação fixa (ex: "Missa ao vivo às 6h, 8h e 18h")'),

            Forms\Components\TextInput::make('programacao_url')
                ->label('URL da programação ao vivo (opcional)')
                ->url()
                ->maxLength(500)
                ->helperText('URL de API JSON com {"programa":"..."}. Sobrepõe o campo Programação em tempo real.'),

            Forms\Components\Section::make('Janela de transmissão')
                ->description('Período em que esta rádio fica no ar. Aparece como horário no player e na lista.')
                ->columns(2)
                ->schema([
                    Forms\Components\TimePicker::make('hora_inicio')
                        ->label('Início da transmissão')
                        ->seconds(false)
                        ->placeholder('08:00'),

                    Forms\Components\TimePicker::make('hora_fim')
                        ->label('Fim da transmissão')
                        ->seconds(false)
                        ->placeholder('09:30'),
                ]),

            Forms\Components\TextInput::make('favicon')
                ->label('URL do Favicon/Logo (opcional)')
                ->url()
                ->maxLength(500),

            Forms\Components\Toggle::make('destaque')
                ->label('Rádio em destaque (aparece no topo)')
                ->default(false),

            Forms\Components\Toggle::make('ativa')
                ->label('Ativa')
                ->default(true),

            Forms\Components\TextInput::make('ordem')
                ->label('Ordem de exibição')
                ->numeric()
                ->default(0),

            Forms\Components\Select::make('categoria')
                ->label('Categoria')
                ->options([
                    'catolica'  => 'Católica',
                    'gospel'    => 'Gospel',
                    'religiosa' => 'Religiosa',
                    'regional'  => 'Regional',
                    'outra'     => 'Outra',
                ])
                ->default('catolica')
                ->required(),

            Forms\Components\Select::make('estado')
                ->label('Estado (UF)')
                ->options([
                    'AC' => 'AC', 'AL' => 'AL', 'AM' => 'AM', 'AP' => 'AP', 'BA' => 'BA',
                    'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
                    'MG' => 'MG', 'MS' => 'MS', 'MT' => 'MT', 'PA' => 'PA', 'PB' => 'PB',
                    'PE' => 'PE', 'PI' => 'PI', 'PR' => 'PR', 'RJ' => 'RJ', 'RN' => 'RN',
                    'RO' => 'RO', 'RR' => 'RR', 'RS' => 'RS', 'SC' => 'SC', 'SE' => 'SE',
                    'SP' => 'SP', 'TO' => 'TO',
                ])
                ->searchable()
                ->nullable(),

            Forms\Components\TextInput::make('cidade')
                ->label('Cidade de origem')
                ->maxLength(80)
                ->nullable(),
        ];
    }
}
