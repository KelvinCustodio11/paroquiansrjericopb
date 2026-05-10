<?php

declare(strict_types=1);

namespace App\Filament\Resources\HistoriaPaginaResource\Pages;

use App\Filament\Resources\HistoriaPaginaResource;
use App\Models\HistoriaPagina;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;

class EditHistoriaPagina extends EditRecord
{
    protected static string $resource = HistoriaPaginaResource::class;

    protected static ?string $title = 'História da Paróquia';

    /**
     * Sempre carrega o registro singleton (id = 1).
     * O valor padrão evita erro de DI quando a rota não tem {record} na URL.
     */
    public function mount(int|string $record = 1): void
    {
        $this->record = HistoriaPagina::current();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publicar')
                ->label('Salvar e Publicar no Site')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publicar alterações no site?')
                ->modalDescription('Isso vai salvar as alterações e regenerar a página historia.html no site estático.')
                ->action(function () {
                    $this->save();
                    Artisan::call('content:export', ['--build' => true]);
                    Notification::make()
                        ->title('Site atualizado com sucesso!')
                        ->body('A página história foi regenerada.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Rascunho salvo. Use "Salvar e Publicar no Site" para atualizar o site.';
    }
}
