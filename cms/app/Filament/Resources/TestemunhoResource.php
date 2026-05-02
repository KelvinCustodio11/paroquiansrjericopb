<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TestemunhoResource\Pages;
use App\Models\Testemunho;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestemunhoResource extends Resource
{
    protected static ?string $model = Testemunho::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Testemunhos';

    protected static ?string $navigationGroup = 'Conteúdo';

    protected static ?int $navigationSort = 11;

    protected static ?string $pluralModelLabel = 'Testemunhos';

    protected static ?string $modelLabel = 'Testemunho';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nome')
                ->label('Nome do fiel')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('cidade')
                ->label('Cidade')
                ->maxLength(80),

            Forms\Components\Textarea::make('texto')
                ->label('Testemunho')
                ->required()
                ->rows(6)
                ->maxLength(2000),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pendente'  => 'Pendente',
                    'aprovado'  => 'Aprovado',
                    'rejeitado' => 'Rejeitado',
                ])
                ->required()
                ->default('pendente'),

            Forms\Components\Textarea::make('motivo_rejeicao')
                ->label('Motivo da rejeição (interno)')
                ->rows(2)
                ->maxLength(500)
                ->visible(fn ($get) => $get('status') === 'rejeitado'),

            Forms\Components\Placeholder::make('email_label')
                ->label('E-mail do remetente (não exibido no site)')
                ->content(fn ($record) => $record?->email ?? '—'),

            Forms\Components\Placeholder::make('consentimento_label')
                ->label('Consentimento LGPD')
                ->content(fn ($record) => $record?->consentimento_lgpd ? '✅ Sim' : '❌ Não'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->default('—'),

                Tables\Columns\TextColumn::make('texto')
                    ->label('Testemunho')
                    ->limit(80),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pendente',
                        'success' => 'aprovado',
                        'danger'  => 'rejeitado',
                    ]),

                Tables\Columns\IconColumn::make('consentimento_lgpd')
                    ->label('LGPD')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente'  => 'Pendentes',
                        'aprovado'  => 'Aprovados',
                        'rejeitado' => 'Rejeitados',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pendente')
                    ->action(fn ($record) => $record->update([
                        'status'      => 'aprovado',
                        'aprovado_em' => now(),
                    ])),

                Tables\Actions\Action::make('rejeitar')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pendente')
                    ->action(fn ($record) => $record->update(['status' => 'rejeitado'])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTestemunhos::route('/'),
            'create' => Pages\CreateTestemunho::route('/create'),
            'edit'   => Pages\EditTestemunho::route('/{record}/edit'),
        ];
    }
}
