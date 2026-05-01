<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PublicarSite extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static ?string $navigationLabel = 'Publicar Site';

    protected static ?string $title = 'Publicar Site';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.publicar-site';

    /** Saída capturada da última execução */
    public string $output = '';

    /** Status: null (aguardando), 'success', 'error' */
    public ?string $status = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicar')
                ->label('Exportar e Publicar Agora')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Publicar Site')
                ->modalDescription('Isso vai exportar todos os dados do banco e regenerar as páginas HTML. Confirma?')
                ->modalSubmitActionLabel('Sim, publicar agora')
                ->action('runBuild'),
        ];
    }

    /** Executa content:export --build e captura a saída */
    public function runBuild(): void
    {
        try {
            Artisan::call('content:export', ['--build' => true]);
            $this->output = Artisan::output();
            $this->status = 'success';

            Notification::make()
                ->title('Site publicado com sucesso!')
                ->success()
                ->send();

            Log::info('PublicarSite: build executado com sucesso', ['output' => $this->output]);
        } catch (\Throwable $e) {
            $this->output = $e->getMessage();
            $this->status = 'error';

            Notification::make()
                ->title('Erro ao publicar site')
                ->body($e->getMessage())
                ->danger()
                ->send();

            Log::error('PublicarSite: falha no build', ['error' => $e->getMessage()]);
        }
    }
}
