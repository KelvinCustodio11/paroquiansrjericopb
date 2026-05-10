<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Radio;
use App\Models\RadioBuscaExterna;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RadioTabs extends BaseWidget
{
    use InteractsWithHeaderActions;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.radio-tabs';

    public string $activeTab = 'radios';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    protected function getTableHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make('nova-radio')
                ->label('Nova Rádio')
                ->model(Radio::class)
                ->visible(fn () => $this->activeTab === 'radios')
                ->form(RadiosTabela::formSchema()),

            \Filament\Actions\CreateAction::make('nova-regra')
                ->label('Nova Regra')
                ->model(RadioBuscaExterna::class)
                ->visible(fn () => $this->activeTab === 'busca-externa')
                ->form(RadioBuscaExternaTabela::formSchema()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(fn (): Builder => $this->activeTab === 'radios'
                ? Radio::query()
                : RadioBuscaExterna::query())
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                // ── Rádios ──────────────────────────────────────────────
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->visible(fn () => $this->activeTab === 'radios'),

                Tables\Columns\TextColumn::make('url')
                    ->label('Stream')
                    ->limit(50)
                    ->visible(fn () => $this->activeTab === 'radios'),

                Tables\Columns\IconColumn::make('destaque')
                    ->label('Destaque')
                    ->boolean()
                    ->visible(fn () => $this->activeTab === 'radios'),

                Tables\Columns\BadgeColumn::make('categoria')
                    ->label('Categoria')
                    ->visible(fn () => $this->activeTab === 'radios'),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Transmissão')
                    ->formatStateUsing(fn ($record) => ($record->hora_inicio ?? null)
                        ? substr($record->hora_inicio, 0, 5) . ' – ' . substr($record->hora_fim ?? '', 0, 5)
                        : '—')
                    ->visible(fn () => $this->activeTab === 'radios'),

                Tables\Columns\IconColumn::make('ativa')
                    ->label('Ativa')
                    ->boolean()
                    ->visible(fn () => $this->activeTab === 'radios'),

                // ── Busca Externa ────────────────────────────────────────
                Tables\Columns\TextColumn::make('label')
                    ->label('Regra')
                    ->searchable()
                    ->visible(fn () => $this->activeTab === 'busca-externa'),

                Tables\Columns\BadgeColumn::make('tag')
                    ->label('Tag')
                    ->default('—')
                    ->visible(fn () => $this->activeTab === 'busca-externa'),

                Tables\Columns\TextColumn::make('pais')
                    ->label('País')
                    ->default('BR')
                    ->visible(fn () => $this->activeTab === 'busca-externa'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->default('—')
                    ->visible(fn () => $this->activeTab === 'busca-externa'),

                Tables\Columns\TextColumn::make('limite')
                    ->label('Limite')
                    ->default('10')
                    ->visible(fn () => $this->activeTab === 'busca-externa'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativa')
                    ->boolean()
                    ->visible(fn () => $this->activeTab === 'busca-externa'),
            ])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn ($record) => $record instanceof Radio
                        ? RadiosTabela::formSchema()
                        : RadioBuscaExternaTabela::formSchema()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
